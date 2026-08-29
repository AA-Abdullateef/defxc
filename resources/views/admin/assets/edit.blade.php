@extends('layouts.admin')
@section('title', 'Edit Asset')
@section('page-title', 'Edit Asset')
@section('topbar-actions')
    <a href="{{ route('admin.assets.index') }}" class="btn btn-ghost btn-sm">Back to Assets</a>
@endsection

@section('content')
<div class="card" style="max-width:520px">
    <div class="card-header"><span class="card-title">Edit {{ strtoupper($asset->name) }}</span></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.assets.update', $asset) }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label">Slug</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $asset->name) }}">
                @error('name')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Display Label</label>
                <input type="text" name="label" class="form-control" value="{{ old('label', $asset->label) }}">
                @error('label')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Icon filename</label>
                <input type="text" name="icon" class="form-control" value="{{ old('icon', $asset->icon) }}">
                @error('icon')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Price Source</label>
                <select name="price_source" class="form-control">
                    <option value="">None</option>
                    <option value="manual" @selected(old('price_source', $asset->price_source) === 'manual')>Manual</option>
                    <option value="coingecko" @selected(old('price_source', $asset->price_source) === 'coingecko')>CoinGecko</option>
                    <option value="alphavantage" @selected(old('price_source', $asset->price_source) === 'alphavantage')>Alpha Vantage</option>
                    <option value="finnhub" @selected(old('price_source', $asset->price_source) === 'finnhub')>Finnhub</option>
                </select>
                @error('price_source')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Price Source ID</label>
                <input type="text" name="price_source_id" class="form-control" value="{{ old('price_source_id', $asset->price_source_id) }}" placeholder="bitcoin or AAPL">
                @error('price_source_id')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Manual USD Price</label>
                <input type="number" step="0.00000001" min="0" name="current_price" class="form-control" value="{{ old('current_price', $asset->current_price) }}" placeholder="1.00000000">
                @error('current_price')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="active" value="1" {{ old('active', $asset->active) ? 'checked' : '' }}>
                    <span class="form-label" style="margin:0">Active</span>
                </label>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('admin.assets.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
