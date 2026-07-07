@extends('layouts.app')

@section('title', 'Report — Zeltechnologies')
@section('page-eyebrow', 'Report')

@section('content')

<div class="grid-2">

    {{-- ── LEFT: Submission Form ──────────────────────────────────────────── --}}
    <div>
        <div class="page-heading">
            <h1><i class="bi bi-file-earmark-text"></i> Weekly Report</h1>
            <p>
                Submissions are open <strong>Thursday to Saturday</strong>.
                Reports after <strong>Friday 8:00 PM</strong> are marked late.
            </p>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>{{ session('error') }}</div>
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

        {{-- Already submitted banner --}}
        @if($submittedThisWeek)
            <div class="alert alert-info">
                <i class="bi bi-info-circle-fill"></i>
                <div>You have submitted your report for this week. Only one submission is allowed per week.</div>
            </div>
        @endif

        {{-- Form --}}
        <div class="card" style="{{ $submittedThisWeek ? 'opacity:.7;' : '' }}">
            <div class="card-header">
                <span class="card-title"><i class="bi bi-pencil-square me-1"></i>Submit Your Report</span>
            </div>
            <div class="card-body">
                <form action="{{ route('reports.store') }}" method="POST" id="report-form"
                      {{ $submittedThisWeek ? 'inert' : '' }}>
                    @csrf

                    {{-- Name --}}
                    <div class="form-group">
                        <label for="name" class="form-label">Full Name <span class="req">*</span></label>
                        <input type="text" id="name" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', auth()->user()->name) }}"
                               placeholder="e.g. John Doe" required />
                        @error('name')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    {{-- Job Title --}}
                    <div class="form-group">
                        <label for="job_title" class="form-label">Job Title <span class="req">*</span></label>
                        <input type="text" id="job_title" name="job_title"
                               class="form-control @error('job_title') is-invalid @enderror"
                               value="{{ old('job_title') }}"
                               placeholder="e.g. Graphic Designer" required />
                        @error('job_title')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    {{-- Report — TipTap rich editor --}}
                    <div class="form-group">
                        <label class="form-label">Weekly Report <span class="req">*</span></label>
                        @include('components.tiptap-editor', [
                            'editorId'    => 'report-editor',
                            'inputName'   => 'report',
                            'placeholder' => 'Describe your work, progress, and accomplishments this week…',
                            'value'       => old('report', ''),
                            'minHeight'   => '220px',
                        ])
                        @error('report')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Challenges — TipTap rich editor --}}
                    <div class="form-group">
                        <label class="form-label">Challenges <span class="text-faint">(optional)</span></label>
                        @include('components.tiptap-editor', [
                            'editorId'    => 'challenges-editor',
                            'inputName'   => 'challenges',
                            'placeholder' => 'Any blockers, risks, or difficulties this week…',
                            'value'       => old('challenges', ''),
                            'minHeight'   => '140px',
                        ])
                        @error('challenges')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="submit-btn" {{ $submittedThisWeek ? 'disabled' : '' }}>
                        <i class="bi bi-send"></i> Submit Report
                    </button>
                </form>
            </div>
        </div>

        {{-- Deadline note --}}
        <p class="text-muted small" style="text-align:center; margin-top:12px;">
            <i class="bi bi-clock me-1"></i>
            Reports submitted after <strong>Friday 8:00 PM</strong> are automatically marked
            <span class="badge badge-late">Late</span>.
        </p>
    </div>

    {{-- ── RIGHT: Submission History ───────────────────────────────────────── --}}
    <div>
        <div class="page-heading">
            <h1 style="font-size:18px;"><i class="bi bi-clock-history"></i> My Submissions</h1>
            <p>Your recent weekly reports.</p>
        </div>

        @forelse($reports as $report)
            <div class="card mb-4">
                <div class="card-body">
                    <div class="flex justify-between items-center mb-2">
                        <div>
                            <span class="fw-semibold small">{{ $report->created_at->format('D, d M Y') }}</span><br>
                            <span class="text-faint" style="font-size: 11px;">
                                Week of {{ $report->created_at->copy()->startOfWeek()->format('d M') }}
                                — {{ $report->created_at->copy()->endOfWeek()->format('d M Y') }}
                            </span>
                        </div>

                        @if($report->status === 'submitted')
                            <span class="badge badge-submitted"><i class="bi bi-clock"></i> Submitted</span>
                        @elseif($report->status === 'completed')
                            <span class="badge badge-completed"><i class="bi bi-check-circle"></i> Completed</span>
                        @else
                            <span class="badge badge-late"><i class="bi bi-exclamation-circle"></i> Late</span>
                        @endif
                    </div>
                    <div class="tiptap-output tiptap-output-clamp text-muted small">
                        {!! $report->report !!}
                    </div>
                </div>
            </div>
        @empty
            <div class="card">
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    No reports submitted yet.
                </div>
            </div>
        @endforelse

        {{-- Pagination --}}
        @if($reports->hasPages())
            {{ $reports->links('vendor.pagination.custom') }}
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/tiptap-init.js') }}"></script>
@endpush
