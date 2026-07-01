@extends('layouts.admin')
@section('title', 'Loan Requests')
@section('page-title', 'Loan Requests')

@section('content')
<div class="card">
    <div class="card-header">
        <span class="card-title">All Loan Requests</span>
        <form method="GET" class="flex gap-2">
            <select name="status" class="form-control" style="width:auto">
                <option value="">All Statuses</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Pending</option>
                <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>Processing</option>
                <option value="3" {{ request('status') === '3' ? 'selected' : '' }}>Approved</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Rejected</option>
            </select>
            <button type="submit" class="btn btn-ghost btn-sm">Filter</button>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>User</th><th>Amount</th><th>Income</th><th>Reason</th><th>Period</th><th>Credit Score</th><th>Status</th><th>Date</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                @php
                    $statusMap = ['0'=>['badge-cancelled','Rejected'],'1'=>['badge-pending','Pending'],'2'=>['badge-processing','Processing'],'3'=>['badge-completed','Approved']];
                    [$cls,$lbl] = $statusMap[$req->status] ?? ['badge-pending','Unknown'];
                @endphp
                <tr>
                    <td>
                        <div style="font-weight:500">{{ $req->user->username }}</div>
                        <div class="td-muted">{{ $req->user->email }}</div>
                    </td>
                    <td class="td-mono">${{ number_format($req->amount, 2) }}</td>
                    <td class="td-mono">${{ number_format($req->income, 2) }}</td>
                    <td class="td-muted">{{ Str::limit($req->reason, 30) }}</td>
                    <td class="td-muted">{{ $req->period }}</td>
                    <td class="td-mono">{{ $req->credit_score }}</td>
                    <td><span class="badge {{ $cls }}">{{ $lbl }}</span></td>
                    <td class="td-muted">{{ $req->created_at->format('d M Y') }}</td>
                    <td>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('admin.loan-requests.status', $req) }}" class="flex gap-2">
                                @csrf
                                @if($req->status === '1')
                                    <button name="status" value="2" class="btn btn-ghost btn-sm">Process</button>
                                    <button name="status" value="3" class="btn btn-success btn-sm">Approve</button>
                                    <button name="status" value="0" class="btn btn-danger btn-sm">Reject</button>
                                @elseif($req->status === '2')
                                    <button name="status" value="3" class="btn btn-success btn-sm">Approve</button>
                                    <button name="status" value="0" class="btn btn-danger btn-sm">Reject</button>
                                @endif
                            </form>
                            <form method="POST" action="{{ route('admin.loan-requests.destroy', $req) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">Del</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" style="text-align:center;color:var(--text-faint);padding:32px">No loan requests found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($requests->hasPages())
    <div style="padding:0 20px">{{ $requests->withQueryString()->links() }}</div>
    @endif
</div>
@endsection