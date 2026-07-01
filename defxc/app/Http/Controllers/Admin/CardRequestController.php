<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CardRequest;
use Illuminate\Http\Request;

class CardRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = CardRequest::with('wallet.user.profile')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(20)->withQueryString();

        return view('admin.requests.cards', compact('requests'));
    }

    public function updateStatus(Request $request, CardRequest $cardRequest)
    {
        $request->validate([
            'status' => ['required', 'string', 'in:pending,approved,rejected'],
        ]);

        $before = $cardRequest->toArray();
        $cardRequest->update(['status' => $request->status]);

        AuditLog::record(
            action:      'card_request.status_updated',
            actorId:     auth()->id(),
            actorType:   'admin',
            subjectType: 'card_request',
            subjectId:   $cardRequest->id,
            before:      $before,
            after:       ['status' => $request->status],
        );

        return back()->with('success', 'Card request status updated.');
    }

    public function destroy(CardRequest $cardRequest)
    {
        AuditLog::record('card_request.deleted', auth()->id(), 'admin', 'card_request', $cardRequest->id, $cardRequest->toArray());
        $cardRequest->delete();

        return redirect()->route('admin.card-requests.index')->with('success', 'Card request deleted.');
    }
}
