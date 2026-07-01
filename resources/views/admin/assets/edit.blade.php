@extends('layouts.admin')
@section('title', 'Edit Asset')
@section('page-title', 'Edit Asset')
@section('topbar-actions')
    <a href="{{ route('admin.assets.index') }}" class="btn btn-ghost btn-sm">← Assets</a>
@endsection

@section('content')
<div class="card" style="max-width:520px">
    <div class="card-header"><span class="card-title">Edit — {{ strtoupper($asset->name) }}</span></div>
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