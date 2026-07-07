@extends('layouts.app')

@section('title', 'Leave Requests — Admin — Zeltechnologies')
@section('page-eyebrow', 'Leave Requests')

@section('content')

@php
    $pendingCount  = $leaveRequests->where('status', 'pending')->count();
    $approvedCount = $leaveRequests->where('status', 'approved')->count();
    $rejectedCount = $leaveRequests->where('status', 'rejected')->count();
@endphp

<div class="page-heading flex justify-between items-center flex-wrap gap-3">
    <div>
        <h1><i class="bi bi-calendar-check"></i> Leave Requests</h1>
        <p>Review and decide on staff leave requests</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <span class="badge badge-submitted">{{ $pendingCount }} Pending</span>
        <span class="badge badge-completed">{{ $approvedCount }} Approved</span>
        <span class="badge badge-late">{{ $rejectedCount }} Rejected</span>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">
        <i class="bi bi-check-circle-fill"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

{{-- Filters --}}
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('admin.leave.index') }}" method="GET">
            <div class="form-row mb-4">
                <div>
                    <label class="form-label">Staff Name</label>
                    <div class="form-icon">
                        <i class="bi bi-person"></i>
                        <input type="text" name="name" class="form-control"
                               placeholder="Search by name…" value="{{ request('name') }}" />
                    </div>
                </div>

                <div>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All</option>
                        <option value="pending"  @selected(request('status') === 'pending')>Pending</option>
                        <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Leave Type</label>
                    <select name="leave_type" class="form-control">
                        <option value="">All</option>
                        @foreach(\App\Models\LeaveRequest::TYPES as $value => $label)
                            <option value="{{ $value }}" @selected(request('leave_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex gap-2" style="justify-content:flex-end;">
                @if(request()->hasAny(['name', 'status', 'leave_type']))
                    <a href="{{ route('admin.leave.index') }}" class="btn btn-ghost"><i class="bi bi-x-lg"></i> Clear</a>
                @endif
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-table me-1"></i>Requests</span>
        <span class="text-muted small">Page {{ $leaveRequests->currentPage() }} of {{ $leaveRequests->lastPage() }}</span>
    </div>

    <div class="card-body" style="padding:0;">
        @if($leaveRequests->isEmpty())
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                No leave requests found matching your criteria.
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Staff</th>
                            <th>Request</th>
                            <th>Dates</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Decided By</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leaveRequests as $leave)
                        <tr>
                            <td class="small">
                                <span class="fw-semibold">{{ $leave->user->name }}</span><br>
                                <span class="text-muted">{{ $leave->user->email }}</span>
                            </td>

                            <td class="small">
                                <span class="fw-semibold">{{ $leave->displayTitle() }}</span><br>
                                <span class="badge" style="background: var(--bg-overlay); color: var(--text-muted); margin-top:4px;">
                                    {{ $leave->leaveTypeLabel() }}
                                </span>
                            </td>

                            <td class="td-mono small">
                                {{ $leave->start_date->format('d M Y') }}<br>
                                <span class="text-muted">to {{ $leave->end_date->format('d M Y') }}</span><br>
                                <span class="text-faint">{{ $leave->daysRequested() }} {{ Str::plural('day', $leave->daysRequested()) }}</span>
                            </td>

                            <td style="max-width: 220px;">
                                <span class="text-muted small">{{ \Illuminate\Support\Str::limit($leave->reason, 90) }}</span>
                            </td>

                            <td>
                                @if($leave->status === 'pending')
                                    <span class="badge badge-submitted"><i class="bi bi-hourglass-split"></i> Pending</span>
                                @elseif($leave->status === 'approved')
                                    <span class="badge badge-completed"><i class="bi bi-check-circle"></i> Approved</span>
                                @else
                                    <span class="badge badge-late"><i class="bi bi-x-circle"></i> Rejected</span>
                                @endif
                            </td>

                            <td class="td-muted small">
                                {{ $leave->decidedBy->name ?? '—' }}
                            </td>

                            <td class="text-center">
                                @if($leave->isPending())
                                    <div class="flex items-center gap-2" style="justify-content:center;">
                                        <form action="{{ route('admin.leave.approve', $leave) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-success btn-sm" title="Approve"
                                                    onclick="return confirm('Approve leave request for {{ addslashes($leave->user->name) }}?')">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.leave.reject', $leave) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Reject"
                                                    onclick="return confirm('Reject leave request for {{ addslashes($leave->user->name) }}?')">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-faint small italic">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if($leaveRequests->hasPages())
        <div class="card-footer">
            <span class="text-muted small">
                Showing {{ $leaveRequests->firstItem() }}–{{ $leaveRequests->lastItem() }} of {{ $leaveRequests->total() }} requests
            </span>
            {{ $leaveRequests->links('vendor.pagination.custom') }}
        </div>
    @endif
</div>

@endsection