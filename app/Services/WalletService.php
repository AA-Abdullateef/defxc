<?php

namespace App\Services;

use App\Models\Wallet;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WalletService
{
    private const SETUP_TOKEN_TTL_MINUTES = 20;
    private const CHALLENGE_WORD_COUNT    = 3;
    private const WORDLIST_PATH           = 'bip39_english.txt';

    /**
     * Step 1: Generate a 12-word BIP39 mnemonic.
     * Words are cached under a setup token — NOT stored in DB at this stage.
     * User must complete the challenge before a wallet record is created.
     */
    public function generateMnemonic(): array
    {
        $words      = $this->randomWords(12);
        $phrase     = implode(' ', $words);
        $setupToken = Str::random(64);

        Cache::put(
            "wallet_setup:{$setupToken}",
            ['phrase' => $phrase, 'words' => $words],
            now()->addMinutes(self::SETUP_TOKEN_TTL_MINUTES)
        );

        return [
            'setup_token' => $setupToken,
            'mnemonic'    => $phrase,
        ];
    }

    /**
     * Step 2: Return 3 random 1-based positions for the user to confirm.
     */
    public function challenge(string $setupToken): array
    {
        $data = $this->resolveSetupToken($setupToken);

        // array_rand on array_fill(1,12,null) returns 0-based keys (1..12 are values, not keys)
        // We need positions 1-12, so fill with keys as values
        $pool      = range(1, 12);
        $positions = (array) array_rand(array_flip($pool), self::CHALLENGE_WORD_COUNT);
        sort($positions);

        $data['challenge'] = $positions;
        Cache::put(
            "wallet_setup:{$setupToken}",
            $data,
            now()->addMinutes(self::SETUP_TOKEN_TTL_MINUTES)
        );

        return ['positions' => $positions];
    }

    /**
     * Step 3: Verify challenge and create a wallet identity.
     *
     * @param  array  $words  keyed by 1-based position e.g. ['3' => 'word', '7' => 'word', '11' => 'word']
     * @throws ValidationException
     */
    public function verifyAndCreate(string $setupToken, array $words): array
    {
        $data      = $this->resolveSetupToken($setupToken);
        $allWords  = $data['words'];
        $challenge = $data['challenge'] ?? [];

        foreach ($challenge as $position) {
            $expected = $allWords[$position - 1] ?? null;
            $provided = $words[(string) $position] ?? null;

            if ($expected === null || strtolower((string) $provided) !== strtolower((string) $expected)) {
                throw ValidationException::withMessages([
                    'words' => 'Mnemonic verification failed. Please check your selected words and try again.',
                ]);
            }
        }

        Cache::forget("wallet_setup:{$setupToken}");

        $wallet = $this->createWalletIdentity($data['phrase']);

        return $this->authenticateWallet($wallet);
    }

    /**
     * Import: locate an existing wallet user by mnemonic fingerprint.
     * No full-table decrypt scan — uses HMAC fingerprint for O(1) lookup.
     *
     * @throws ValidationException
     */
    public function importByMnemonic(string $phrase): array
    {
        $fingerprint   = Wallet::fingerprint($phrase);
        $publicKey     = Wallet::publicKey($phrase);
        $mnemonicHash  = Wallet::mnemonicHash($phrase);

        $wallet = Wallet::query()
            ->where('fingerprint', $fingerprint)
            ->orWhere('public_key', $publicKey)
            ->orWhere('mnemonic_hash', $mnemonicHash)
            ->first();

        if (! $wallet) {
            throw ValidationException::withMessages([
                'mnemonic' => ['No account found for this mnemonic phrase.'],
            ]);
        }

        return $wallet->user
            ? $this->authenticateUserWallet($wallet)
            : $this->authenticateWallet($wallet);
    }

    // ─── Private ─────────────────────────────────────────────────────────────────

    private function resolveSetupToken(string $token): array
    {
        $data = Cache::get("wallet_setup:{$token}");

        if (! $data) {
            throw ValidationException::withMessages([
                'setup_token' => ['Setup token is invalid or has expired.'],
            ]);
        }

        return $data;
    }

    private function createWalletIdentity(string $phrase): Wallet
    {
        return Wallet::updateOrCreate(
            ['fingerprint' => Wallet::fingerprint($phrase)],
            [
                'id'            => (string) Str::uuid(),
                'user_id'       => null,
                'mnemonic'      => $phrase,
                'mnemonic_hash' => Wallet::mnemonicHash($phrase),
                'public_key'    => Wallet::publicKey($phrase),
            ]
        );
    }

    private function authenticateWallet(Wallet $wallet): array
    {
        $wallet->tokens()->delete();

        $token = $wallet->createToken('wallet')->plainTextToken;

        return [
            'access_token'            => $token,
            'auth_type'        => 'wallet',
            'wallet'           => $wallet->fresh('user'),
            'user'             => null,
            'requires_profile' => false,
        ];
    }

    private function authenticateUserWallet(Wallet $wallet): array
    {
        $user = $wallet->user;

        $user->tokens()->delete();

        $token = $user->createToken('api')->plainTextToken;

        return [
            'access_token'            => $token,
            'auth_type'        => 'user',
            'wallet'           => $wallet,
            'user'             => $user->load('profile', 'photo'),
            'requires_profile' => ! $user->profile_completed,
        ];
    }

    private function randomWords(int $count): array
    {
        $path = storage_path('app/' . self::WORDLIST_PATH);

        if (file_exists($path)) {
            $all  = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $keys = (array) array_rand($all, $count);
            return array_map(fn ($k) => $all[$k], $keys);
        }

        // Fallback — replace with full 2048-word BIP39 list in production
        $fallback = file(
            base_path('english.txt'),
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        );

        if ($fallback === false) {
            die('Unable to load BIP39 word list.');
        }

        shuffle($fallback);

        return array_slice($fallback, 0, $count);
    }
}
