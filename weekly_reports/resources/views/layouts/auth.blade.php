<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Zeltechnologies — Weekly Reports')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon" />

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@400;500;600&family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

    {{-- Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

    {{-- Auth styles --}}
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}" />
</head>
<body>

<div class="login-wrap">
    <div class="login-brand">
        <div class="name"><i class="bi bi-lightning-charge-fill" style="color:#16a34a;"></i> Zeltechnologies</div>
        <div class="label">Weekly Report Portal</div>
    </div>

    <div class="login-card">
        @yield('content')
    </div>

    <div class="page-footer">&copy; {{ date('Y') }} Zeltechnologies. All rights reserved.</div>
</div>

</body>
</html>
