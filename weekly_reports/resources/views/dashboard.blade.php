@extends('layouts.app')

@section('title', 'Dashboard — Zeltechnologies')
@section('page-eyebrow')
    <span>Welcome, <strong>{{ $user->name }}</strong></span>
@endsection

@section('content')

<div class="page-heading">
    <h1><i class="bi bi-grid"></i> Dashboard</h1>
    <p>
        You're signed in as {{ $user->isAdmin() ? 'an administrator' : 'staff' }}.
        Use the sidebar to submit your weekly report{{ $user->isAdmin() ? ' or review everyone\'s submissions' : '' }}.
    </p>
</div>

<div class="card">
    <div class="card-body">
        <div class="empty-state">
            <i class="bi bi-hourglass-split"></i>
            <div class="fw-semibold mb-2">Nothing here yet</div>
            <p class="text-muted small">
                This dashboard is the shared landing page for every account. More widgets
                (recent activity, quick stats) are coming soon.
            </p>
        </div>
    </div>
</div>

<div class="grid-3 mb-4" style="margin-top: 20px;">
    <a href="{{ route('reports.index') }}" class="card" style="text-decoration:none; display:block;">
        <div class="card-body flex items-center gap-3">
            <i class="bi bi-file-earmark-text" style="font-size:22px; color: var(--brand);"></i>
            <div>
                <div class="fw-semibold" style="color: var(--text-primary);">Report</div>
                <div class="text-muted small">Submit your weekly report or review your history</div>
            </div>
        </div>
    </a>

    <a href="{{ route('leave.index') }}" class="card" style="text-decoration:none; display:block;">
        <div class="card-body flex items-center gap-3">
            <i class="bi bi-calendar-check" style="font-size:22px; color: var(--brand);"></i>
            <div>
                <div class="fw-semibold" style="color: var(--text-primary);">Leave Request</div>
                <div class="text-muted small">Apply for leave or check your request status</div>
            </div>
        </div>
    </a>

    @if($user->isAdmin())
        <a href="{{ route('admin.reports.index') }}" class="card" style="text-decoration:none; display:block;">
            <div class="card-body flex items-center gap-3">
                <i class="bi bi-people" style="font-size:22px; color: var(--brand);"></i>
                <div>
                    <div class="fw-semibold" style="color: var(--text-primary);">All Reports</div>
                    <div class="text-muted small">Review and manage every submission</div>
                </div>
            </div>
        </a>

        @if($user->hasPermission('manage-leave-requests'))
            <a href="{{ route('admin.leave.index') }}" class="card" style="text-decoration:none; display:block;">
                <div class="card-body flex items-center gap-3">
                    <i class="bi bi-calendar-check" style="font-size:22px; color: var(--brand);"></i>
                    <div>
                        <div class="fw-semibold" style="color: var(--text-primary);">Leave Requests</div>
                        <div class="text-muted small">Review and decide on staff leave requests</div>
                    </div>
                </div>
            </a>
        @endif
    @endif
</div>

@endsection
