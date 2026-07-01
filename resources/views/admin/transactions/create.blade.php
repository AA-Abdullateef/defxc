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
            <div class="form-group">
                <label class="form-label">User</label>
                <select name="user_id" class="form-control">
                    <option value="">— Select User —</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id') === $user->id ? 'selected' : '' }}>
                            {{ $user->username }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
                @error('user_id')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-control">
                        @foreach(['deposit','withdrawal','transfer'] as $t)
                            <option value="{{ $t }}" {{ old('type') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                    @error('type')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Asset</label>
                    <select name="asset_id" class="form-control">
                        <option value="">— Select Asset —</option>
                        @foreach($assets as $asset)
                            <option value="{{ $asset->id }}" {{ old('asset_id') === $asset->id ? 'selected' : '' }}>
                                {{ $asset->label }} ({{ $asset->name }})
                            </option>
                        @endforeach
                    </select>
                    @error('asset_id')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Sub-Method <span style="color:var(--text-faint)">(optional)</span></label>
                    <select name="sub_method_id" class="form-control">
                        <option value="">— None —</option>
                        @foreach($methods as $method)
                            <optgroup label="{{ $method->name }}">
                                @foreach($method->subMethods as $sub)
                                    <option value="{{ $sub->id }}" {{ old('sub_method_id') === $sub->id ? 'selected' : '' }}>
                                        {{ $sub->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.00001" name="amount" class="form-control" value="{{ old('amount') }}" placeholder="0.00000">
                    @error('amount')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        @foreach(['pending','completed','cancelled'] as $s)
                            <option value="{{ $s }}" {{ old('status', 'pending') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Reference <span style="color:var(--text-faint)">(wallet address / notes)</span></label>
                    <input type="text" name="reference" class="form-control" value="{{ old('reference') }}" placeholder="Optional">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Create Transaction</button>
        </form>
    </div>
</div>
@endsection