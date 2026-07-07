<?php

namespace App\Http\Controllers;

use App\Mail\LeaveRequestDecided;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AdminLeaveController extends Controller
{
    /**
     * All leave requests across every staff member — filterable, paginated.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'name'       => ['nullable', 'string'],
            'status'     => ['nullable', 'in:pending,approved,rejected'],
            'leave_type' => ['nullable', 'string', 'in:' . implode(',', array_keys(LeaveRequest::TYPES))],
        ]);

        $query = LeaveRequest::with(['user', 'decidedBy'])->latest();

        if ($request->filled('name')) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', '%' . $validated['name'] . '%'));
        }

        if ($request->filled('status')) {
            $query->where('status', $validated['status']);
        }

        if ($request->filled('leave_type')) {
            $query->where('leave_type', $validated['leave_type']);
        }

        $leaveRequests = $query->paginate(15)->withQueryString();

        return view('admin.leave.index', compact('leaveRequests'));
    }

    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        $this->decide($request, $leaveRequest, 'approved');

        return back()->with('success', "Leave request for \"{$leaveRequest->user->name}\" has been approved.");
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $this->decide($request, $leaveRequest, 'rejected');

        return back()->with('success', "Leave request for \"{$leaveRequest->user->name}\" has been rejected.");
    }

    private function decide(Request $request, LeaveRequest $leaveRequest, string $status): void
    {
        abort_unless($leaveRequest->isPending(), 422, 'Only pending leave requests can be decided on.');

        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $leaveRequest->update([
            'status'     => $status,
            'decided_by' => Auth::id(),
            'decided_at' => now(),
            'admin_note' => $validated['admin_note'] ?? null,
        ]);

        $leaveRequest->load('user');
        Mail::to($leaveRequest->user->email)->queue(new LeaveRequestDecided($leaveRequest));
    }
}