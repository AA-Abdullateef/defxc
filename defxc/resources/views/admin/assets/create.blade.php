@extends('layouts.admin')
@section('title', 'New Asset')
@section('page-title', 'New Asset')
@section('topbar-actions')
    <a href="{{ route('admin.assets.index') }}" class="btn btn-ghost btn-sm">← Assets</a>
@endsection

@section('content')
<div class="card" style="max-width:520px">
    <div class="card-header"><span class="card-title">Create Asset</span></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.assets.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Slug <span style="color:var(--text-faint)">(lowercase, e.g. btc)</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="usd">
                @error('name')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Display Label</label>
                <input type="text" name="label" class="form-control" value="{{ old('label') }}" placeholder="US Dollar">
                @error('label')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Icon filename <span style="color:var(--text-faint)">(e.g. btc.png)</span></label>
                <input type="text" name="icon" class="form-control" value="{{ old('icon') }}" placeholder="btc.png">
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="active" value="1" {{ old('active', true) ? 'checked' : '' }}>
                    <span class="form-label" style="margin:0">Active</span>
                </label>
            </div>
            <button type="submit" class="btn btn-primary">Create Asset</button>
        </form>
    </div>
</div>
@endsection