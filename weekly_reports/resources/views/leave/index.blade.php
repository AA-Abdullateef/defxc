@extends('layouts.app')

@section('title', 'Leave Requests — Zeltechnologies')
@section('page-eyebrow', 'Leave Requests')

@section('content')

<div class="grid-2">

    {{-- ── LEFT: Submission Form ──────────────────────────────────────────── --}}
    <div>
        <div class="page-heading">
            <h1><i class="bi bi-calendar-check"></i> Apply for Leave</h1>
            <p>Submit a leave request — your admin will be notified and you'll get an email once it's decided.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>
                    <strong>Please fix the errors below:</strong>
                    <ul>
                        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="bi bi-pencil-square me-1"></i>New Leave Request</span>
            </div>
            <div class="card-body">
                <form action="{{ route('leave.store') }}" method="POST">
                    @csrf

                    <div class="form-row mb-4">
                        <div>
                            <label for="leave_type" class="form-label">Leave Type <span class="req">*</span></label>
                            <select id="leave_type" name="leave_type"
                                    class="form-control @error('leave_type') is-invalid @enderror" required>
                                @foreach($leaveTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('leave_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('leave_type')<div class="field-error">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label for="leave_title" class="form-label">Title <span class="text-faint">(optional)</span></label>
                            <input type="text" id="leave_title" name="leave_title"
                                   class="form-control @error('leave_title') is-invalid @enderror"
                                   value="{{ old('leave_title') }}" placeholder="e.g. Family trip" />
                            @error('leave_title')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-row mb-4">
                        <div>
                            <label for="start_date" class="form-label">Start Date <span class="req">*</span></label>
                            <input type="date" id="start_date" name="start_date"
                                   class="form-control @error('start_date') is-invalid @enderror"
                                   value="{{ old('start_date') }}" required />
                            @error('start_date')<div class="field-error">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label for="end_date" class="form-label">End Date <span class="req">*</span></label>
                            <input type="date" id="end_date" name="end_date"
                                   class="form-control @error('end_date') is-invalid @enderror"
                                   value="{{ old('end_date') }}" required />
                            @error('end_date')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reason" class="form-label">Reason <span class="req">*</span></label>
                        <textarea id="reason" name="reason" rows="5"
                                  class="form-control @error('reason') is-invalid @enderror"
                                  placeholder="Briefly explain the reason for your leave…" required>{{ old('reason') }}</textarea>
                        @error('reason')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="bi bi-send"></i> Submit Request
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── RIGHT: History ──────────────────────────────────────────────────── --}}
    <div>
        <div class="page-heading">
            <h1 style="font-size:18px;"><i class="bi bi-clock-history"></i> My Leave Requests</h1>
            <p>Status of your past and pending requests.</p>
        </div>

        @forelse($leaveRequests as $leave)
            <div class="card mb-4">
                <div class="card-body">
                    <div class="flex justify-between items-center mb-2">
                        <div>
                            <span class="fw-semibold small">{{ $leave->displayTitle() }}</span><br>
                            <span class="text-faint" style="font-size: 11px;">
                                {{ $leave->start_date->format('d M Y') }} &mdash; {{ $leave->end_date->format('d M Y') }}
                                &middot; {{ $leave->daysRequested() }} {{ Str::plural('day', $leave->daysRequested()) }}
                            </span>
                        </div>

                        @if($leave->status === 'pending')
                            <span class="badge badge-submitted"><i class="bi bi-hourglass-split"></i> Pending</span>
                        @elseif($leave->status === 'approved')
                            <span class="badge badge-completed"><i class="bi bi-check-circle"></i> Approved</span>
                        @else
                            <span class="badge badge-late"><i class="bi bi-x-circle"></i> Rejected</span>
                        @endif
                    </div>

                    <span class="badge" style="background: var(--bg-overlay); color: var(--text-muted); margin-bottom: 8px;">
                        {{ $leave->leaveTypeLabel() }}
                    </span>
                    <p class="text-muted small mb-2" style="margin-top:6px;">{{ $leave->reason }}</p>

                    @if($leave->admin_note)
                        <div class="divider"></div>
                        <p class="text-faint small" style="margin-top:8px;">
                            <i class="bi bi-chat-left-text me-1"></i><strong>Admin note:</strong> {{ $leave->admin_note }}
                        </p>
                    @endif
                </div>
            </div>
        @empty
            <div class="card">
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    No leave requests yet.
                </div>
            </div>
        @endforelse

        @if($leaveRequests->hasPages())
            {{ $leaveRequests->links('vendor.pagination.custom') }}
        @endif
    </div>
</div>

@endsection