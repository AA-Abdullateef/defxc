<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Events\TransferInitiated;
use App\Http\Controllers\API\V1\AccountController;
use App\Models\Asset;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\FundsService;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class FundsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_creates_paired_legs_with_shared_transfer_id(): void
    {
        Event::fake([TransferInitiated::class]);

        $asset = $this->asset();
        $sender = $this->wallet('sender');
        $recipient = $this->wallet('recipient');

        $this->completedDeposit($sender, $asset, 10);

        $transfer = app(FundsService::class)->createTransfer($sender, [
            'asset_id' => $asset->id,
            'amount' => 1,
            'recipient_id' => $recipient->id,
        ]);

        $incoming = Transaction::query()
            ->where('wallet_id', $recipient->id)
            ->where('type', TransactionType::Transfer->value)
            ->firstOrFail();

        $this->assertSame('outgoing', $transfer->meta['direction']);
        $this->assertSame('incoming', $incoming->meta['direction']);
        $this->assertSame($transfer->meta['transfer_id'], $incoming->meta['transfer_id']);
        $this->assertSame($incoming->id, $transfer->relatedPendingTransferLeg()?->id);
        $this->assertSame($transfer->id, $incoming->relatedPendingTransferLeg()?->id);
    }

    public function test_duplicate_transfers_are_paired_by_transfer_id_not_amount_heuristics(): void
    {
        Event::fake([TransferInitiated::class]);

        $asset = $this->asset();
        $sender = $this->wallet('sender');
        $recipient = $this->wallet('recipient');

        $this->completedDeposit($sender, $asset, 10);

        $first = app(FundsService::class)->createTransfer($sender, [
            'asset_id' => $asset->id,
            'amount' => 1,
            'recipient_id' => $recipient->id,
        ]);

        $second = app(FundsService::class)->createTransfer($sender, [
            'asset_id' => $asset->id,
            'amount' => 1,
            'recipient_id' => $recipient->id,
        ]);

        $firstIncoming = Transaction::query()
            ->where('wallet_id', $recipient->id)
            ->where('meta->transfer_id', $first->meta['transfer_id'])
            ->firstOrFail();

        $secondIncoming = Transaction::query()
            ->where('wallet_id', $recipient->id)
            ->where('meta->transfer_id', $second->meta['transfer_id'])
            ->firstOrFail();

        $this->assertNotSame($first->meta['transfer_id'], $second->meta['transfer_id']);
        $this->assertSame($firstIncoming->id, $first->relatedPendingTransferLeg()?->id);
        $this->assertSame($secondIncoming->id, $second->relatedPendingTransferLeg()?->id);
    }

    public function test_pending_transfer_debits_sender_until_paired_completion(): void
    {
        Event::fake([TransferInitiated::class]);

        $asset = $this->asset();
        $sender = $this->wallet('sender');
        $recipient = $this->wallet('recipient');
        $ledger = app(LedgerService::class);

        $this->completedDeposit($sender, $asset, 10);

        $outgoing = app(FundsService::class)->createTransfer($sender, [
            'asset_id' => $asset->id,
            'amount' => 2,
            'recipient_id' => $recipient->id,
        ]);

        $this->assertSame(8.0, $ledger->balanceFor($sender->id, $asset->id));
        $this->assertSame(0.0, $ledger->balanceFor($recipient->id, $asset->id));

        $incoming = $outgoing->relatedPendingTransferLeg();
        $outgoing->update(['status' => TransactionStatus::Completed->value]);
        $incoming?->update(['status' => TransactionStatus::Completed->value]);

        $this->assertSame(8.0, $ledger->balanceFor($sender->id, $asset->id));
        $this->assertSame(2.0, $ledger->balanceFor($recipient->id, $asset->id));
    }

    public function test_total_usd_balance_uses_positive_prices_and_reports_unpriced_assets(): void
    {
        $wallet = $this->wallet('holder');
        $usd = $this->asset('usd', 1);
        $btc = $this->asset('btc', 50000);
        $unpriced = $this->asset('mystery');

        $this->completedDeposit($wallet, $usd, 100);
        $this->completedDeposit($wallet, $btc, 0.5);
        $this->completedDeposit($wallet, $unpriced, 20);

        $total = app(LedgerService::class)->totalUsdBalanceFor($wallet->id);

        $this->assertSame(25100.0, $total['total_usd']);
        $this->assertSame(1, $total['unpriced_asset_count']);
    }

    public function test_dashboard_returns_legacy_balance_key_as_total_usd_balance(): void
    {
        $wallet = $this->wallet('dashboard');
        $usd = $this->asset('usd', 1);
        $btc = $this->asset('btc', 50000);

        $this->completedDeposit($wallet, $usd, 100);
        $this->completedDeposit($wallet, $btc, 0.5);

        $request = Request::create('/api/v1/dashboard', 'GET');
        $request->setUserResolver(fn () => $wallet);

        $response = app(AccountController::class)->dashboard($request);
        $payload = $response->getData(true);

        $this->assertEquals(25100.0, $payload['data']['balance']);
        $this->assertEquals(25100.0, $payload['data']['portfolio_usd']['total_usd']);
        $this->assertArrayHasKey('balances', $payload['data']);
        $this->assertArrayHasKey('asset_balances', $payload['data']);
    }

    private function asset(string $name = 'btc', ?float $price = null): Asset
    {
        return Asset::forceCreate([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'label' => strtoupper($name),
            'active' => true,
            'price_source' => $price === null ? null : 'manual',
            'current_price' => $price,
        ]);
    }

    private function wallet(string $suffix): Wallet
    {
        return Wallet::forceCreate([
            'id' => (string) Str::uuid(),
            'mnemonic_hash' => hash('sha256', "wallet-{$suffix}"),
            'fingerprint' => hash('sha256', "fingerprint-{$suffix}"),
            'public_key' => hash('sha256', "public-{$suffix}"),
        ]);
    }

    private function completedDeposit(Wallet $wallet, Asset $asset, float $amount): Transaction
    {
        return Transaction::create([
            'wallet_id' => $wallet->id,
            'asset_id' => $asset->id,
            'type' => TransactionType::Deposit->value,
            'amount' => $amount,
            'status' => TransactionStatus::Completed->value,
            'reference' => (string) str()->uuid(),
        ]);
    }
}
