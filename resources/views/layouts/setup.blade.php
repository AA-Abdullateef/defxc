<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Wallet Setup')</title>

    <!-- Simple, clean fallback styles for the setup screen layout -->
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .setup-card {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            max-width: 450px;
            width: 100%;
        }
        h2 { margin-top: 0; color: #111827; font-size: 1.5rem; }
        p { color: #4b5563; font-size: 0.95rem; line-height: 1.5; }
        .btn-login {
            display: inline-block;
            width: 100%;
            padding: 12px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            text-align: center;
        }
        .btn-login:hover { background-color: #1d4ed8; }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 0.9rem; }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1rem;
        }
    </style>
</head>
<body>

    <!-- This is where user/setup/generate.blade.php injections drop in -->
    @yield('content')

</body>
</html>
