<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'user'     => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($credentials['user'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $attempt = Auth::attempt([
            $field    => strtolower($credentials['user']),
            'password' => $credentials['password'],
        ], $request->boolean('remember'));

        if (! $attempt) {
            return back()->withErrors(['user' => 'Invalid credentials.'])->withInput();
        }

        if (! Auth::user()->isAdmin()) {
            Auth::logout();
            return back()->withErrors(['user' => 'Access denied.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}