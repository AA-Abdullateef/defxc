<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Country;
use App\Models\Asset;
use App\Models\Method;
use App\Models\SubMethod;
use App\Models\User;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\Transaction;
use App\Models\DepositPhoto;
use App\Models\CardRequest;
use App\Models\UserToken;
use App\Models\Wallet;
use App\Models\WalletConnection;

class TestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    DB::transaction(function () {

        // Base Seeders
        $this->call([
            CountrySeeder::class,
            AssetSeeder::class,
            MethodSeeder::class,
        ]);

        $country = Country::where('name', 'Nigeria')->first()
            ?? Country::inRandomOrder()->first();

        /*
        |--------------------------------------------------------------------------
        | Admin User
        |--------------------------------------------------------------------------
        */
        $admin = User::create([
            'id'        => (string) Str::uuid(),
            'username'   => 'admin',
            'email'      => 'admin@example.com',
            'country_id' => $country?->id,
            'admin'      => true,
            'status'     => 'active',
            'two_factor' => true,
            'password'   => 'password',
            'profile_completed' => true,
            'email_verified_at' => now(),
        ]);

        Profile::create([
            'id'         => (string) Str::uuid(),
            'user_id'    => $admin->id,
            'first_name' => 'System',
            'last_name'  => 'Administrator',
            'gender'     => 'male',
            'phone'      => '+2348000000000',
            'state'      => 'Lagos',
            'address'    => 'Admin Office',
            'zip'        => '100001',
        ]);

        $phrase = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about admin';

        Wallet::firstOrCreate(
            ['user_id' => $admin->id], // lookup field
            [
                'id'              => (string) Str::uuid(),
                'mnemonic'        => $phrase,
                'mnemonic_hash'   => Wallet::mnemonicHash($phrase),
                'fingerprint'     => Wallet::fingerprint($phrase),
                'public_key'      => Wallet::publicKey($phrase),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */
        $users = collect();

        for ($i = 1; $i <= 10; $i++) {

            $user = User::create([
                'id'          => (string) Str::uuid(),
                'username'    => "user{$i}",
                'email'       => "user{$i}@example.com",
                'country_id'  => $country?->id,
                'referrer_id' => $i <= 4 ? $admin->id : null,
                'status'      => 'active',
                'password'    => 'password',
                'profile_completed' => true,
                'email_verified_at' => now(),
            ]);

            Profile::create([
                'id'         => (string) Str::uuid(),
                'user_id'    => $user->id,
                'first_name' => fake()->firstName(),
                'last_name'  => fake()->lastName(),
                'gender'     => fake()->randomElement(['male','female']),
                'phone'      => fake()->phoneNumber(),
                'state'      => fake()->state(),
                'address'    => fake()->address(),
                'zip'        => fake()->postcode(),
                'dob'        => fake()->date(),
            ]);

            if ($i <= 5) {
                ProfilePhoto::create([
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'img' => 'profiles/default-avatar.png',
                ]);
            }

            $users->push($user);
        }

        /*
        |--------------------------------------------------------------------------
        | Sub Methods 'method_id', 'name', 'account_name', 'account_number', 'bank_name', 'routing_number', 'swift_code', 'iban', 'wallet_address', 'network', 'instructions', 'is_active'
        |--------------------------------------------------------------------------
        */
        $methods = Method::pluck('id', 'name');

        $subMethods = collect([
            [
                'method_name' => 'Crytocurrency',
                'name' => 'Ethereum Mainnet',
                'wallet_address' => '0x71C7656EC7ab88b098defB751B7401B5f6d8976F',
                'network' => 'Ethereum',
                'instructions' => 'Use the Ethereum Mainnet for deposits and withdrawals.',
            ],

            [
                'method_name' => 'Crytocurrency',
                'name' => 'TRC20 Network',
                'wallet_address' => 'TLyqzVGLV1srkB7dToTAEqgDrZ36dc36Nk',
                'network' => 'Tron',
                'instructions' => 'Use the TRC20 network for USDT transactions.',
            ],

            [
                'method_name' => 'Bank Transfer',
                'name' => 'Standard Bank Transfer',
                'account_name' => 'DEFXC Financial Platform',
                'account_number' => '1234567890',
                'bank_name' => 'Example Bank',
                'routing_number' => '021000021',
                'instructions' => 'Use the provided bank account details when making deposits.',
            ],
        ])
        ->map(function ($subMethod) use ($methods) {

            return [
                'id' => (string) Str::uuid(),

                'method_id' => $methods->get($subMethod['method_name']),

                'name' => $subMethod['name'],

                'account_name' => $subMethod['account_name'] ?? null,
                'account_number' => $subMethod['account_number'] ?? null,
                'bank_name' => $subMethod['bank_name'] ?? null,

                'routing_number' => $subMethod['routing_number'] ?? null,
                'swift_code' => null,
                'iban' => null,

                'wallet_address' => $subMethod['wallet_address'] ?? null,
                'network' => $subMethod['network'] ?? null,

                'instructions' => $subMethod['instructions'],

                'is_active' => true,

                'created_at' => now(),
                'updated_at' => now(),
            ];
        })
        ->filter(fn ($subMethod) => filled($subMethod['method_id']))
        ->values()
        ->all();

        SubMethod::insert($subMethods);

        /*
        |--------------------------------------------------------------------------
        | Transactions
        |--------------------------------------------------------------------------
        */
        $assetIds = Asset::pluck('id')->toArray();
        $subMethodIds = SubMethod::pluck('id')->toArray();

        $wallets = collect();

        foreach ($users as $user) {

            // 💡 Define the phrase variable here first before using it below
            $phrase = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about ' . $user->id;

            $wallet = Wallet::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'mnemonic' => $phrase, // 🔥 This will now work perfectly
                'mnemonic_hash' => Wallet::mnemonicHash($phrase),
                'fingerprint' => Wallet::fingerprint($phrase),
                'public_key' => Wallet::publicKey($phrase),
            ]);

            $wallets->push($wallet);
        }

        $transactions = [];
        $depositPhotos = [];

        foreach ($wallets as $wallet) {

            /*
            |--------------------------------------------------------------------------
            | Deposit
            |--------------------------------------------------------------------------
            */

            $depositId = (string) Str::uuid();

            $transactions[] = [
                'id' => $depositId,
                'wallet_id' => $wallet->id,
                'amount' => rand(500, 5000),
                'type' => 'deposit',
                'asset_id' => Arr::random($assetIds),
                'sub_method_id' => Arr::random($subMethodIds),
                'status' => 'completed',
                'reference' => 'completed-deposit',
                'meta' => json_encode([
                    'source' => 'seed'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $depositPhotos[] = [
                'id' => (string) Str::uuid(),
                'transaction_id' => $depositId,
                'img' => 'deposits/proof.png',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            /*
            |--------------------------------------------------------------------------
            | Withdrawal
            |--------------------------------------------------------------------------
            */

            $transactions[] = [
                'id' => (string) Str::uuid(),
                'wallet_id' => $wallet->id,
                'amount' => rand(100, 500),
                'type' => 'withdrawal',
                'asset_id' => Arr::random($assetIds),
                'sub_method_id' => Arr::random($subMethodIds),
                'status' => Arr::random([
                    'pending',
                    'completed',
                    'cancelled',
                ]),
                'reference' => '0x' . Str::random(40),
                'meta' => json_encode([
                    'destination' => 'external-wallet'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            /*
            |--------------------------------------------------------------------------
            | Transfer
            |--------------------------------------------------------------------------
            */

            $recipient = $users
                ->where('id', '!=', $wallet->user_id)
                ->random();

            $transactions[] = [
                'id' => (string) Str::uuid(),
                'wallet_id' => $wallet->id,
                'amount' => rand(50, 300),
                'type' => 'transfer',
                'asset_id' => Arr::random($assetIds),
                'sub_method_id' => null,
                'status' => 'completed',
                'reference' => 'internal-transfer',
                'meta' => json_encode([
                    'recipient_id' => $recipient->id,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Transaction::insert($transactions);

        DepositPhoto::insert($depositPhotos);

        /*
        |--------------------------------------------------------------------------
        | Card Requests
        |--------------------------------------------------------------------------
        */
        foreach ($wallets->take(5) as $wallet) {
            CardRequest::create([
                'id'          => (string) Str::uuid(),
                'wallet_id'      => $wallet->id,
                'amount'       => rand(1000, 3000),
                'credit_score' => rand(650, 800),
                'status'       => Arr::random(['pending', 'approved', 'rejected']),
                'type'         => 'Virtual Visa',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Tokens / Mnemonics / Connections
        |--------------------------------------------------------------------------
        */
        foreach ($users->take(5) as $user) {

            UserToken::create([
                'id'         => (string) Str::uuid(),
                'user_id'    => $user->id,
                'token'      => Str::random(64),
                'purpose'    => 'email_verification',
                'expires_at' => now()->addHour(),
            ]);

            WalletConnection::create([
                'id' => (string) Str::uuid(),
                'wallet_id' => $wallet->id,
                'wallet'  => 'MetaMask',
                'address' => '0x' . Str::random(40),
                'details' => json_encode(['network' => 'Ethereum']),
            ]);
        }

        $this->command->info('Test data seeded successfully.');
    });
}
}
