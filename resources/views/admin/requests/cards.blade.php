@extends('layouts.admin')

@section('title', 'Card Requests')
@section('page-title', 'Card Requests')

@section('content')
<div class="card">
    <div class="card-header">
        <span class="card-title">All Card Requests</span>

        <form method="GET" class="flex gap-2">
            <select name="status" class="form-control" style="width:auto">
                <option value="">All Statuses</option>

                @foreach(\App\Enums\RequestStatus::cases() as $status)
                    <option
                        value="{{ $status->value }}"
                        @selected(request('status') === $status->value)
                    >
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-ghost btn-sm">
                Filter
            </button>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Credit Score</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($requests as $request)

                    @php
                        $status = \App\Enums\RequestStatus::tryFrom($request->status);
                        $user = $request->wallet?->user;
                    @endphp

                    <tr>
                        <td>
                            <div style="font-weight:500">
                                {{ $user?->username ?? 'Unregistered wallet' }}
                            </div>

                            <div class="td-muted">
                                {{ $user?->email ?? $request->wallet_id }}
                            </div>
                        </td>

                        <td class="td-muted">
                            {{ $request->type ?: '—' }}
                        </td>

                        <td class="td-mono">
                            ${{ number_format($request->amount, 2) }}
                        </td>

                        <td class="td-mono">
                            {{ $request->credit_score }}
                        </td>

                        <td>
                            @if($status)
                                <span class="badge {{ $status->badgeClass() }}">
                                    {{ $status->label() }}
                                </span>
                            @else
                                <span class="badge badge-pending">
                                    Unknown
                                </span>
                            @endif
                        </td>

                        <td class="td-muted">
                            {{ $request->created_at->format('d M Y') }}
                        </td>

                        <td>
                            <div class="flex gap-2">

                                @if($status === \App\Enums\RequestStatus::Pending)

                                    <form
                                        method="POST"
                                        action="{{ route('admin.card-requests.status', $request) }}"
                                        class="flex gap-2"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            name="status"
                                            value="{{ \App\Enums\RequestStatus::Approved->value }}"
                                            class="btn btn-success btn-sm"
                                        >
                                            Approve
                                        </button>

                                        <button
                                            type="submit"
                                            name="status"
                                            value="{{ \App\Enums\RequestStatus::Rejected->value }}"
                                            class="btn btn-danger btn-sm"
                                        >
                                            Reject
                                        </button>
                                    </form>

                                @endif

                                <form
                                    method="POST"
                                    action="{{ route('admin.card-requests.destroy', $request) }}"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        class="btn btn-danger btn-sm"
                                        onclick="return confirm('Delete this request?')"
                                    >
                                        Delete
                                    </button>
                                </form>

                            </div>
                        </td>
                    </tr>

                @empty

                    <tr>
                        <td colspan="7" class="text-center py-8 td-muted">
                            No card requests found.
                        </td>
                    </tr>

                @endforelse
            </tbody>
        </table>
    </div>

    @if($requests->hasPages())
        <div style="padding:0 20px">
            {{ $requests->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
