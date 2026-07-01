<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Login') — {{ config('defxc.company_short') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:ital,wght@0,300;0,400;0,500;1,300&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;0,9..144,500;1,9..144,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    @stack('styles')
</head>
<body>
    <div class="login-wrap">
        <div class="login-brand">
            <div class="name">{{ config('defxc.company_short') }}</div>
            <div class="label">Admin Portal</div>
        </div>

        @yield('content')

        <div class="login-footer">
            &copy; {{ date('Y') }} {{ config('defxc.company_full_name') }}
        </div>
    </div>
    @stack('scripts')
</body>
</html>