@extends('layouts.app')

@section('title', 'Report — ' . $report->name . ' — Zeltechnologies')
@section('page-eyebrow', 'Report Detail')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.reports.index') }}"><i class="bi bi-people"></i> All Reports</a>
    <span class="sep">/</span>
    <span>Report #{{ $report->id }}</span>
</div>

@if(session('success'))
    <div class="alert alert-success">
        <i class="bi bi-check-circle-fill"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

@php
    $statusBadges = [
        'submitted' => ['class' => 'badge-submitted', 'icon' => 'bi-clock', 'label' => 'Submitted'],
        'completed' => ['class' => 'badge-completed', 'icon' => 'bi-check-circle', 'label' => 'Completed'],
        'late'      => ['class' => 'badge-late', 'icon' => 'bi-exclamation-circle', 'label' => 'Late'],
    ];
    $statusBadge = $statusBadges[$report->status] ?? ['class' => 'badge-submitted', 'icon' => 'bi-question-circle', 'label' => ucfirst($report->status)];
@endphp

{{-- Header meta card --}}
<div class="card mb-4">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-file-earmark-person me-1"></i>Weekly Report — #{{ $report->id }}</span>
        <span class="badge {{ $statusBadge['class'] }}"><i class="bi {{ $statusBadge['icon'] }}"></i> {{ $statusBadge['label'] }}</span>
    </div>

    <div class="card-body">
        <div class="detail-grid">
            <div>
                <div class="detail-item-label"><i class="bi bi-person me-1"></i>Employee Name</div>
                <div class="detail-item-value lg">{{ $report->name }}</div>
            </div>

            <div>
                <div class="detail-item-label"><i class="bi bi-briefcase me-1"></i>Job Title</div>
                <div class="detail-item-value fw-semibold">{{ $report->job_title }}</div>
            </div>

            <div>
                <div class="detail-item-label"><i class="bi bi-person-badge me-1"></i>Submitted By (Account)</div>
                <div class="detail-item-value">
                    {{ $report->user->name }}
                    <span class="text-muted small">— {{ $report->user->email }}</span>
                </div>
            </div>

            <div>
                <div class="detail-item-label"><i class="bi bi-calendar3 me-1"></i>Submitted</div>
                <div class="detail-item-value">
                    {{ $report->created_at->format('l, d F Y') }}
                    <span class="text-muted small ms-1">at {{ $report->created_at->format('g:i A') }}</span>
                </div>
            </div>

            <div>
                <div class="detail-item-label"><i class="bi bi-calendar-week me-1"></i>Report Week</div>
                <div class="detail-item-value text-muted">
                    {{ $report->created_at->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->format('d M') }}
                    &mdash;
                    {{ $report->created_at->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)->format('d M Y') }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Report body --}}
<div class="card mb-4">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-journal-richtext me-1"></i>Weekly Report</span>
    </div>
    <div class="card-body">
        <div class="tiptap-output">{!! $report->report !!}</div>
    </div>
</div>

{{-- Challenges --}}
<div class="card mb-4">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-exclamation-triangle me-1"></i>Challenges</span>
    </div>
    <div class="card-body">
        @if($report->challenges)
            <div class="tiptap-output">{!! $report->challenges !!}</div>
        @else
            <p class="text-faint italic">
                <i class="bi bi-dash-circle me-1"></i>No challenges reported for this week.
            </p>
        @endif
    </div>
</div>

{{-- Action bar --}}
<div class="flex justify-between items-center flex-wrap gap-3">
    <a href="{{ route('admin.reports.index') }}" class="btn btn-ghost">
        <i class="bi bi-arrow-left"></i> Back to All Reports
    </a>

    @if($report->status === 'submitted')
        <form action="{{ route('admin.reports.accept', $report) }}" method="POST">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-success"
                    onclick="return confirm('Mark this report from {{ addslashes($report->name) }} as completed?')">
                <i class="bi bi-check-circle"></i> Complete Report
            </button>
        </form>
    @elseif($report->status === 'completed')
        <span class="fw-semibold" style="color: var(--green);">
            <i class="bi bi-check-circle-fill me-1"></i>This report has been completed.
        </span>
    @else
        <span class="fw-semibold" style="color: var(--red);">
            <i class="bi bi-exclamation-circle-fill me-1"></i>Submitted after Friday 8:00 PM.
        </span>
    @endif
</div>

@endsection
