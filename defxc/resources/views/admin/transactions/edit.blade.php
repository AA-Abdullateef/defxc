@section('title', 'Edit Transaction')
@section('page-title', 'Edit Transaction')
@section('topbar-actions')
    <a href="{{ route('admin.transactions.index') }}" class="btn btn-ghost btn-sm">Back to Transactions</a>
@endsection

@section('content')
@php($user = $transaction->wallet?->user)
<div class="card" style="max-width:680px">
    <div class="card-header">
        <span class="card-title">Edit Transaction</span>
        <span class="td-mono" style="font-size:11px;color:var(--text-faint)">{{ $transaction->id }}</span>
    </div>
    <div class="card-body">

        {{-- Read-only context --}}
        <div class="detail-grid mb-6">
            <div class="detail-item">
                <div class="detail-item-label">User</div>
                <div class="detail-item-value">{{ $user?->username ?? 'Unregistered wallet' }} - {{ $user?->email ?? $transaction->wallet_id }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-item-label">Type</div>
                <div class="detail-item-value"><span class="badge badge-pending">{{ $transaction->type }}</span></div>
            </div>
            <div class="detail-item">
                <div class="detail-item-label">Created</div>
                <div class="detail-item-value">{{ $transaction->created_at->format('d M Y, H:i') }}</div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.transactions.update', $transaction) }}">
            @csrf @method('PUT')
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Asset</label>
                    <select name="asset_id" class="form-control">
                        @foreach($assets as $asset)
                            <option value="{{ $asset->id }}" {{ old('asset_id', $transaction->asset_id) === $asset->id ? 'selected' : '' }}>
                                {{ $asset->label }} ({{ $asset->name }})
                            </option>
                        @endforeach
                    </select>
                    @error('asset_id')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Sub-Method</label>
                    <select name="sub_method_id" class="form-control">
                        <option value="">None</option>
                        @foreach($methods as $method)
                            <optgroup label="{{ $method->name }}">
                                @foreach($method->subMethods as $sub)
                                    <option value="{{ $sub->id }}"
                                        {{ old('sub_method_id', $transaction->sub_method_id) === $sub->id ? 'selected' : '' }}>
                                        {{ $sub->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.00001" name="amount" class="form-control"
                           value="{{ old('amount', $transaction->amount) }}">
                    @error('amount')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        @foreach(['pending','completed','cancelled'] as $s)
                            <option value="{{ $s }}" {{ old('status', $transaction->status) === $s ? 'selected' : '' }}>
                                {{ ucfirst($s) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Reference</label>
                    <input type="text" name="reference" class="form-control"
                           value="{{ old('reference', $transaction->reference) }}"
                           placeholder="Wallet address, transfer ID, notes...">
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('admin.transactions.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
