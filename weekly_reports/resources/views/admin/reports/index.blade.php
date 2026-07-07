@extends('layouts.app')

@section('title', 'All Reports — Zeltechnologies')
@section('page-eyebrow', 'All Reports')

@section('content')

@php
    $submittedReports = $reports->where('status', 'submitted')->count();
    $completedReports = $reports->where('status', 'completed')->count();
    $lateReports = $reports->where('status', 'late')->count();
@endphp

<div class="page-heading flex justify-between items-center flex-wrap gap-3">
    <div>
        <h1><i class="bi bi-people"></i> All Reports</h1>
        <p>Manage every weekly report submission across the team</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <span class="badge badge-submitted">{{ $submittedReports }} {{ Str::plural('Submitted', $submittedReports) }}</span>
        <span class="badge badge-completed">{{ $completedReports }} {{ Str::plural('Completed', $completedReports) }}</span>
        <span class="badge badge-late">{{ $lateReports }} {{ Str::plural('Late', $lateReports) }}</span>
    </div>
</div>

{{-- Session Alerts --}}
@if(session('success'))
    <div class="alert alert-success">
        <i class="bi bi-check-circle-fill"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

{{-- Filters --}}
<div class="card mb-4">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-funnel me-1"></i>Filter Reports</span>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.reports.index') }}" method="GET">
            <div class="form-row mb-4">
                <div>
                    <label class="form-label">Name</label>
                    <div class="form-icon">
                        <i class="bi bi-person"></i>
                        <input type="text" name="name" class="form-control"
                               placeholder="Search by name…" value="{{ request('name') }}" />
                    </div>
                </div>

                <div>
                    <label class="form-label">Job Title</label>
                    <div class="form-icon">
                        <i class="bi bi-briefcase"></i>
                        <input type="text" name="job_title" class="form-control"
                               placeholder="Search by title…" value="{{ request('job_title') }}" />
                    </div>
                </div>

                <div>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All</option>
                        <option value="submitted" @selected(request('status') === 'submitted')>Submitted</option>
                        <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                        <option value="late"      @selected(request('status') === 'late')>Late</option>
                    </select>
                </div>

                <div>
                    <label for="filter_from_date" class="form-label">From Date</label>
                    <input type="date" id="filter_from_date" name="from_date"
                           class="form-control" value="{{ request('from_date') }}" />
                </div>

                <div>
                    <label for="filter_to_date" class="form-label">To Date</label>
                    <input type="date" id="filter_to_date" name="to_date"
                           class="form-control" value="{{ request('to_date') }}" />
                </div>
            </div>

            <div class="flex justify-between items-center" style="justify-content:flex-end;">
                <div class="flex gap-2">
                    @if(request()->hasAny(['name', 'job_title', 'status', 'from_date', 'to_date']))
                        <a href="{{ route('admin.reports.index') }}" class="btn btn-ghost">
                            <i class="bi bi-x-lg"></i> Clear
                        </a>
                    @endif
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Reports Table --}}
<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-table me-1"></i>Submitted Reports</span>
        <span class="text-muted small">Page {{ $reports->currentPage() }} of {{ $reports->lastPage() }}</span>
    </div>

    <div class="card-body" style="padding:0;">
        @if($reports->isEmpty())
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                No reports found matching your criteria.
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Name / Title</th>
                            <th>Report</th>
                            <th>Challenges</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reports as $report)
                        <tr>
                            <td class="td-muted">{{ $report->id }}</td>

                            <td class="small">
                                <span class="fw-semibold">{{ $report->user->name }}</span><br>
                                <span class="text-muted">{{ $report->user->email }}</span>
                            </td>

                            <td>
                                <div class="fw-semibold">{{ $report->name }}</div>
                                <div class="text-muted small">{{ $report->job_title }}</div>
                            </td>

                            <td style="max-width: 220px;">
                                <div class="tiptap-output text-muted" style="max-height: 48px; overflow: hidden; font-size: 12px;">
                                    {!! $report->report !!}
                                </div>
                            </td>

                            <td style="max-width: 160px;">
                                @if($report->challenges)
                                    <div class="tiptap-output text-muted" style="max-height: 48px; overflow: hidden; font-size: 12px;">
                                        {!! $report->challenges !!}
                                    </div>
                                @else
                                    <span class="text-faint italic small">—</span>
                                @endif
                            </td>

                            <td>
                                @if($report->status === 'submitted')
                                    <span class="badge badge-submitted"><i class="bi bi-clock"></i> Submitted</span>
                                @elseif($report->status === 'completed')
                                    <span class="badge badge-completed"><i class="bi bi-check-circle"></i> Completed</span>
                                @else
                                    <span class="badge badge-late"><i class="bi bi-exclamation-circle"></i> Late</span>
                                @endif
                            </td>

                            <td class="td-muted" style="white-space:nowrap;">
                                {{ $report->created_at->format('d M Y') }}<br>
                                <span>{{ $report->created_at->format('g:i A') }}</span>
                            </td>

                            <td class="text-center">
                                <div class="flex items-center justify-between gap-2" style="justify-content:center;">
                                    <a href="{{ route('admin.reports.show', $report) }}"
                                       class="btn btn-ghost btn-sm" title="View full report">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    @if($report->status === 'submitted')
                                        <form action="{{ route('admin.reports.accept', $report) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="btn btn-success btn-sm"
                                                    title="Complete report"
                                                    onclick="return confirm('Mark report from {{ addslashes($report->name) }} as completed?')">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Pagination --}}
    @if($reports->hasPages())
        <div class="card-footer">
            <span class="text-muted small">
                Showing {{ $reports->firstItem() }}–{{ $reports->lastItem() }} of {{ $reports->total() }} reports
            </span>
            {{ $reports->links('vendor.pagination.custom') }}
        </div>
    @endif
</div>

@endsection
