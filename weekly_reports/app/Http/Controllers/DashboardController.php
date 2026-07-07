<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Shared landing page for every authenticated user, regardless of role.
     *
     * Deliberately minimal for now — this is the single place both admins
     * and staff land on after login. Role-specific work (My Reports vs.
     * All Reports) is reached from here via the sidebar, and each of those
     * destinations enforces its own access control independently.
     */
    public function index()
    {
        $user = Auth::user();

        return view('dashboard', compact('user'));
    }
}
