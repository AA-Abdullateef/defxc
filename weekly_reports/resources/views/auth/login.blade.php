@extends('layouts.auth')

@section('title', 'Login — Zeltechnologies')

@section('content')

<h5>Sign in to your account</h5>

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<form action="{{ route('login') }}" method="POST">
    @csrf

    {{-- Email --}}
    <div class="form-group">
        <label for="email" class="form-label">Email address</label>
        <div class="form-icon">
            <i class="bi bi-envelope"></i>
            <input type="email" id="email" name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" placeholder="you@example.com"
                   autocomplete="email" autofocus required />
        </div>
        @error('email')
            <div class="field-error">{{ $message }}</div>
        @enderror
    </div>

    {{-- Password --}}
    <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <div class="form-icon">
            <i class="bi bi-lock"></i>
            <input type="password" id="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="••••••••" autocomplete="current-password" required />
        </div>
        @error('password')
            <div class="field-error">{{ $message }}</div>
        @enderror
    </div>

    {{-- Remember --}}
    <div class="remember-row">
        <div class="check">
            <input type="checkbox" name="remember" id="remember" />
            <label for="remember">Remember me</label>
        </div>
        @if(Route::has('password.request'))
            <a href="{{ route('password.request') }}">Forgot password?</a>
        @endif
    </div>

    <button type="submit" class="btn-login">
        <i class="bi bi-box-arrow-in-right"></i> Sign In
    </button>
</form>

<p class="login-footer">
    Don't have an account?
    <a href="{{ route('register') }}">Register</a>
</p>

@endsection
