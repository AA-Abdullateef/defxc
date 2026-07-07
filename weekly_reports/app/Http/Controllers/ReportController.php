<?php

namespace App\Http\Controllers;

use App\Models\WeeklyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * Show the authenticated user's "My Reports" page — submission form
     * plus their own submission history.
     */
    public function index()
    {
        $user = Auth::user();

        // Load the user's reports newest first for the history panel
        $reports = $user->weeklyReports()
            ->latest()
            ->paginate(5);

        // Has the user already submitted this week?
        $submittedThisWeek = WeeklyReport::existsForUserThisWeek($user->id);

        return view('reports.index', compact('user', 'reports', 'submittedThisWeek'));
    }

    /**
     * Handle report submission.
     *
     * Security note: the report field accepts HTML from the TipTap editor.
     * We run it through a whitelist-based purifier to strip anything dangerous
     * (scripts, event handlers, iframes, etc.) while preserving formatting tags.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'job_title'  => ['required', 'string', 'max:255'],
            'report'     => ['required', 'string'],
            'challenges' => ['nullable', 'string'],
        ]);

        $user = Auth::user();

        // Duplicate prevention — one report per user per ISO week
        if (! WeeklyReport::isSubmissionOpen()) {
            return back()
                ->withInput()
                ->with('error', 'Weekly report submission is only open from Thursday through Saturday.');
        }

        if (WeeklyReport::existsForUserThisWeek($user->id)) {
            return back()
                ->withInput()
                ->with('error', 'You have already submitted a report for this week. Only one submission per week is allowed.');
        }

        // Sanitise HTML — strip dangerous tags/attributes, keep formatting
        $validated['report']     = $this->sanitiseHtml($validated['report']);
        $validated['challenges'] = $validated['challenges']
            ? $this->sanitiseHtml($validated['challenges'])
            : null;

        $validated['user_id'] = $user->id;
        $validated['status']  = WeeklyReport::resolveStatus();

        WeeklyReport::create($validated);

        return redirect()->route('reports.index')
            ->with('success', 'Your weekly report has been submitted successfully!');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Whitelist-based HTML sanitiser.
     *
     * Allowed tags mirror what TipTap StarterKit + formatting extensions produce.
     * Everything not on the allow-list (including script, iframe, on* handlers) is stripped.
     *
     * NOTE: If HTMLPurifier / mews/purifier is installed, swap this body for:
     *       return Purifier::clean($html);
     *
     * This built-in implementation covers the common case without extra dependencies.
     */
    private function sanitiseHtml(string $html): string
    {
        // Tags we're happy to store
        $allowedTags = '<p><br><strong><em><u><s><ul><ol><li><blockquote>'
                     . '<h1><h2><h3><h4><h5><h6><a><code><pre><hr><mark>';

        $clean = strip_tags($html, $allowedTags);

        // Strip event handlers (onclick, onerror …) and javascript: hrefs
        $clean = preg_replace('/\bon\w+\s*=\s*"[^"]*"/i', '', $clean);
        $clean = preg_replace('/\bon\w+\s*=\s*\'[^\']*\'/i', '', $clean);
        $clean = preg_replace('/href\s*=\s*["\']?\s*javascript:[^"\'>\s]*/i', 'href="#"', $clean);

        return $clean;
    }
}
