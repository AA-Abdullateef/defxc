<?php

namespace App\Http\Controllers;

use App\Mail\LeaveRequestSubmitted;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class LeaveRequestController extends Controller
{
    /**
     * The authenticated user's own leave request history + submission form.
     */
    public function index()
    {
        $leaveRequests = Auth::user()
            ->leaveRequests()
            ->latest()
            ->paginate(10);

        $leaveTypes = LeaveRequest::TYPES;

        return view('leave.index', compact('leaveRequests', 'leaveTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'leave_type'  => ['required', 'string', 'in:' . implode(',', array_keys(LeaveRequest::TYPES))],
            'leave_title' => ['nullable', 'string', 'max:150'],
            'start_date'  => ['required', 'date', 'after_or_equal:today'],
            'end_date'    => ['required', 'date', 'after_or_equal:start_date'],
            'reason'      => ['required', 'string', 'max:2000'],
        ]);

        $this->guardAgainstOverlap(Auth::user(), $validated['start_date'], $validated['end_date']);

        $leaveRequest = Auth::user()->leaveRequests()->create($validated);

        // Notify everyone with manage-leave-requests permission (+ all super_admins)
        foreach (User::withPermission('manage-leave-requests') as $recipient) {
            Mail::to($recipient->email)->queue(new LeaveRequestSubmitted($leaveRequest));
        }

        return redirect()->route('leave.index')
            ->with('success', 'Your leave request has been submitted.');
    }

    /**
     * Block a new request that overlaps an existing pending/approved one for
     * the same user. Rejected/cancelled requests don't count against this.
     */
    private function guardAgainstOverlap(User $user, string $startDate, string $endDate): void
    {
        $overlaps = $user->leaveRequests()
            ->whereIn('status', ['pending', 'approved'])
            ->where(fn ($q) => $q
                ->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(fn ($q2) => $q2
                    ->where('start_date', '<=', $startDate)
                    ->where('end_date', '>=', $endDate)
                )
            )
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages([
                'start_date' => 'You already have a pending or approved leave request that overlaps these dates.',
            ]);
        }
    }
}