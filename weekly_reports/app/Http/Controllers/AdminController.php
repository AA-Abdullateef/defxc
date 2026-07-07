<?php

namespace App\Http\Controllers;

use App\Models\WeeklyReport;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * All Reports — filterable, paginated report list across every user.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string'],
            'job_title' => ['nullable', 'string'],
            'status' => ['nullable', 'in:submitted,completed,late'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $query = WeeklyReport::with('user')->latest();

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $validated['name'] . '%');
        }

        if ($request->filled('job_title')) {
            $query->where('job_title', 'like', '%' . $validated['job_title'] . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $validated['status']);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $validated['from_date']);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $validated['to_date']);
        }

        $reports = $query->paginate(15)->withQueryString();

        return view('admin.reports.index', compact('reports'));
    }

    /**
     * Show a single report in full detail.
     */
    public function show(WeeklyReport $report)
    {
        $report->load('user');

        return view('admin.reports.show', compact('report'));
    }

    /**
     * Mark a submitted report as completed.
     */
    public function accept(WeeklyReport $report)
    {
        abort_unless($report->status === 'submitted', 422, 'Only submitted reports can be completed.');

        $report->update(['status' => 'completed']);

        return back()->with('success', "Report for \"{$report->name}\" has been completed.");
    }
}
