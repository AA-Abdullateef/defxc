@extends('layouts.admin')
@section('title', 'New Transaction')
@section('page-title', 'New Transaction')
@section('topbar-actions')
    <a href="{{ route('admin.transactions.index') }}" class="btn btn-ghost btn-sm">← Transactions</a>
@endsection

@section('content')
<div class="card" style="max-width:680px">
    <div class="card-header"><span class="card-title">Create Transaction</span></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.transactions.store') }}">
            @csrf

            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Wallet</label>
                    <select name="wallet_id" class="form-control">
                        <option value="">— Select Wallet —</option>
                        @foreach($wallets as $wallet)
                            <option value="{{ $wallet->id }}" {{ old('wallet_id') === $wallet->id ? 'selected' : '' }}>
                                {{ $wallet->id }} — {{ $wallet->user?->username ?? 'Unregistered' }}
                            </option>
                        @endforeach
                    </select>
                    @error('wallet_id')<div class="form-error">{{ $message }}</div>@enderror
                    <div class="td-muted" style="margin-top:4px">Every wallet account is listed, registered or not.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" id="type-select" class="form-control">
                        <option value="">— Select Type —</option>
                        @foreach(\App\Enums\TransactionType::cases() as $t)
                            <option value="{{ $t->value }}" {{ old('type') === $t->value ? 'selected' : '' }}>{{ $t->label() }}</option>
                        @endforeach
                    </select>
                    @error('type')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Deposit / Withdrawal --}}
            <div data-field-group="deposit-withdrawal" class="grid-2">
                <div class="form-group">
                    <label class="form-label">Asset</label>
                    <select name="asset_id" class="form-control field-asset">
                        <option value="">— Select Asset —</option>
                        @foreach($assets as $asset)
                            <option value="{{ $asset->id }}" {{ old('asset_id') === $asset->id ? 'selected' : '' }}>{{ $asset->label }} ({{ $asset->name }})</option>
                        @endforeach
                    </select>
                    @error('asset_id')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.00000001" name="amount" class="form-control field-amount" value="{{ old('amount') }}" placeholder="0.00000000">
                    @error('amount')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Sub-Method <span style="color:var(--text-faint)">(optional — cryptocurrency methods)</span></label>
                    <select name="sub_method_id" class="form-control field-sub-method-crypto">
                        <option value="">— None —</option>
                        @foreach($cryptoMethods as $method)
                            <optgroup label="{{ $method->name }}">
                                @foreach($method->subMethods as $sub)
                                    <option value="{{ $sub->id }}" {{ old('sub_method_id') === $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Buy --}}
            <div data-field-group="buy" class="grid-2">
                <div class="form-group">
                    <label class="form-label">Asset to buy</label>
                    <select name="asset_id" class="form-control field-asset">
                        <option value="">— Select Asset —</option>
                        @foreach($assets as $asset)
                            <option value="{{ $asset->id }}" {{ old('asset_id') === $asset->id ? 'selected' : '' }}>{{ $asset->label }} ({{ $asset->name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Fiat Amount</label>
                    <input type="number" step="0.01" name="fiat_amount" class="form-control" value="{{ old('fiat_amount') }}" placeholder="0.00">
                    @error('fiat_amount')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Sub-Method <span style="color:var(--text-faint)">(optional — bank/fiat methods)</span></label>
                    <select name="sub_method_id" class="form-control field-sub-method-fiat">
                        <option value="">— None —</option>
                        @foreach($fiatMethods as $method)
                            <optgroup label="{{ $method->name }}">
                                @foreach($method->subMethods as $sub)
                                    <option value="{{ $sub->id }}" {{ old('sub_method_id') === $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Sell --}}
            <div data-field-group="sell" class="grid-2">
                <div class="form-group">
                    <label class="form-label">Asset to sell</label>
                    <select name="asset_id" class="form-control field-asset">
                        <option value="">— Select Asset —</option>
                        @foreach($assets as $asset)
                            <option value="{{ $asset->id }}" {{ old('asset_id') === $asset->id ? 'selected' : '' }}>{{ $asset->label }} ({{ $asset->name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.00000001" name="amount" class="form-control field-amount" value="{{ old('amount') }}" placeholder="0.00000000">
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Sub-Method <span style="color:var(--text-faint)">(optional — bank/fiat methods)</span></label>
                    <select name="sub_method_id" class="form-control field-sub-method-fiat">
                        <option value="">— None —</option>
                        @foreach($fiatMethods as $method)
                            <optgroup label="{{ $method->name }}">
                                @foreach($method->subMethods as $sub)
                                    <option value="{{ $sub->id }}" {{ old('sub_method_id') === $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Transfer --}}
            <div data-field-group="transfer" class="grid-2">
                <div class="form-group">
                    <label class="form-label">Asset</label>
                    <select name="asset_id" class="form-control field-asset">
                        <option value="">— Select Asset —</option>
                        @foreach($assets as $asset)
                            <option value="{{ $asset->id }}" {{ old('asset_id') === $asset->id ? 'selected' : '' }}>{{ $asset->label }} ({{ $asset->name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.00000001" name="amount" class="form-control field-amount" value="{{ old('amount') }}" placeholder="0.00000000">
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Recipient Wallet</label>
                    <select name="recipient_wallet_id" class="form-control">
                        <option value="">— Select Recipient —</option>
                        @foreach($wallets as $wallet)
                            <option value="{{ $wallet->id }}" {{ old('recipient_wallet_id') === $wallet->id ? 'selected' : '' }}>{{ $wallet->id }} — {{ $wallet->user?->username ?? 'Unregistered' }}</option>
                        @endforeach
                    </select>
                    @error('recipient_wallet_id')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Swap --}}
            <div data-field-group="swap" class="grid-2">
                <div class="form-group">
                    <label class="form-label">From Asset</label>
                    <select name="asset_id" class="form-control field-asset">
                        <option value="">— Select Asset —</option>
                        @foreach($assets as $asset)
                            <option value="{{ $asset->id }}" {{ old('asset_id') === $asset->id ? 'selected' : '' }}>{{ $asset->label }} ({{ $asset->name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">To Asset</label>
                    <select name="to_asset_id" class="form-control">
                        <option value="">— Select Asset —</option>
                        @foreach($assets as $asset)
                            <option value="{{ $asset->id }}" {{ old('to_asset_id') === $asset->id ? 'selected' : '' }}>{{ $asset->label }} ({{ $asset->name }})</option>
                        @endforeach
                    </select>
                    @error('to_asset_id')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Amount <span style="color:var(--text-faint)">(of the From asset)</span></label>
                    <input type="number" step="0.00000001" name="amount" class="form-control field-amount" value="{{ old('amount') }}" placeholder="0.00000000">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        @foreach(\App\Enums\TransactionStatus::cases() as $s)
                            @continue($s->value !== 'pending' && $s->value !== 'completed')
                            <option value="{{ $s->value }}" {{ old('status', 'pending') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="form-error">{{ $message }}</div>@enderror
                    <div class="td-muted" style="margin-top:4px">Completed applies immediately and notifies the user, same as clicking "Complete" after creating.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Reference <span style="color:var(--text-faint)">(optional)</span></label>
                    <input type="text" name="reference" class="form-control" value="{{ old('reference') }}" placeholder="Optional note">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Create Transaction</button>
        </form>
    </div>
</div>

<script>
(function () {
    var GROUPS_BY_TYPE = {
        deposit: ['deposit-withdrawal'],
        withdrawal: ['deposit-withdrawal'],
        buy: ['buy'],
        sell: ['sell'],
        transfer: ['transfer'],
        swap: ['swap'],
    };

    var typeSelect = document.getElementById('type-select');
    var allGroups = document.querySelectorAll('[data-field-group]');

    function sync() {
        var visible = GROUPS_BY_TYPE[typeSelect.value] || [];
        allGroups.forEach(function (group) {
            var show = visible.indexOf(group.dataset.fieldGroup) !== -1;
            group.style.display = show ? '' : 'none';
            group.querySelectorAll('input, select').forEach(function (el) {
                el.disabled = !show;
            });
        });
    }

    typeSelect.addEventListener('change', sync);
    sync();
})();
</script>
@endsection
