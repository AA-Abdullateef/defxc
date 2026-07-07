@extends('layouts.auth')

@section('title', 'Register — Zeltechnologies')

@section('content')

<h5>Create your account</h5>

<form action="{{ route('register') }}" method="POST">
    @csrf

    {{-- Name --}}
    <div class="form-group">
        <label for="name" class="form-label">Full Name</label>
        <div class="form-icon">
            <i class="bi bi-person"></i>
            <input type="text" id="name" name="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}" placeholder="John Doe"
                   autocomplete="name" autofocus required />
        </div>
        @error('name')
            <div class="field-error">{{ $message }}</div>
        @enderror
    </div>

    {{-- Email --}}
    <div class="form-group">
        <label for="email" class="form-label">Email address</label>
        <div class="form-icon">
            <i class="bi bi-envelope"></i>
            <input type="email" id="email" name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" placeholder="you@example.com"
                   autocomplete="email" required />
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
                   placeholder="Min. 8 characters" autocomplete="new-password" required />
        </div>
        @error('password')
            <div class="field-error">{{ $message }}</div>
        @enderror
    </div>

    {{-- Confirm Password --}}
    <div class="form-group">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <div class="form-icon">
            <i class="bi bi-lock-fill"></i>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   class="form-control"
                   placeholder="Re-enter password" autocomplete="new-password" required />
        </div>
    </div>

    <button type="submit" class="btn-login">
        <i class="bi bi-person-plus"></i> Create Account
    </button>
</form>

<p class="login-footer">
    Already have an account?
    <a href="{{ route('login') }}">Sign in</a>
</p>

@endsection
