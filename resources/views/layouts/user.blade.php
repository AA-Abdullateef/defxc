<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('defxc.company_short') }} User</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:ital,wght@0,300;0,400;0,500&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;0,9..144,500;1,9..144,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @stack('styles')
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="app-name">{{ config('defxc.company_short') }}</div>
        <div class="app-label">User Portal</div>
    </div>

    <nav class="sidebar-nav">

        <div class="nav-section-label">Overview</div>
        <a href="{{ route('dashboard') }}"
           class="nav-item {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" /></svg>
            Dashboard
        </a>

        <div class="nav-section-label">Finance</div>
        <a href="{{ route('user.transactions.index') }}"
           class="nav-item {{ request()->routeIs('user.transactions.*') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
            Transactions
        </a>
        <a href="{{ route('user.deposits.index') }}"
           class="nav-item {{ request()->routeIs('user.deposits.*') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15M9 12l3 3m0 0 3-3m-3 3V2.25" /></svg>
            Deposit
        </a>
        <a href="{{ route('user.withdrawals.index') }}"
           class="nav-item {{ request()->routeIs('user.withdrawals.*') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15m0-3-3-3m0 0L9 5.25M12 2.25v13.5" /></svg>
            Withdraw
        </a>
        <a href="{{ route('user.transfers.index') }}"
           class="nav-item {{ request()->routeIs('user.transfers.*') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
            Transfer
        </a>

        <div class="nav-section-label">Platform</div>
        <a href="{{ route('user.assets.index') }}"
           class="nav-item {{ request()->routeIs('user.assets.*') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" /></svg>
            Assets
        </a>

        <div class="nav-section-label">Account</div>
        <a href="{{ route('user.profile.index') }}"
           class="nav-item {{ request()->routeIs('user.profile.*') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
            Profile
        </a>
        <a href="{{ route('user.referrals.index') }}"
           class="nav-item {{ request()->routeIs('user.referrals.*') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
            Referrals
        </a>

        <div class="nav-section-label">Requests</div>
        <a href="{{ route('user.wallets.index') }}"
           class="nav-item {{ request()->routeIs('user.wallets.*') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18-3a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3m18-3v3M3 9h18" /></svg>
            Wallets
        </a>
        <a href="{{ route('user.card-requests.index') }}"
           class="nav-item {{ request()->routeIs('user.card-requests.*') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
            Card Requests
        </a>

    </nav>

    <div class="sidebar-footer">
        <a href="{{ route('user.profile.index') }}" class="sidebar-user" style="text-decoration:none; color:inherit;">
            <span id="sidebar-username">Account</span>
        </a>
        <!-- 💡 UPDATED: Added structural form intercept ID for API routing -->
        <form id="sidebar-logout-form" method="POST" action="">
            @csrf
            <button type="submit" style="background:none;border:none;color:var(--text-muted);font-size:12px;cursor:pointer;padding:0;font-family:var(--font-body);">
                Sign out →
            </button>
        </form>
    </div>
</aside>

<div class="main-wrap">
    <header class="topbar">
        <div class="topbar-title">
            {{ config('defxc.company_short') }} / <strong>@yield('page-title', 'Dashboard')</strong>
        </div>
        <div class="topbar-actions" style="display:flex; align-items:center; gap:12px;">
            <a href="#" id="admin-panel-link" style="display:none; font-size:12px; font-weight:600; color:var(--text-muted); text-decoration:none; border:1px solid var(--border); padding:4px 10px; border-radius:4px;">
                Admin Panel ↗
            </a>
            @yield('topbar-actions')
        </div>
    </header>

    <main class="page-content">
        @if(session('success'))
            <div class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>

                <div>
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<!-- Layout bootstrap: hydrate username + show admin link if applicable -->
<script>
(async () => {
    const token = localStorage.getItem('auth_token');
    if (!token) return;

    try {
        const response = await fetch("{{ url('api/v1/me') }}", {
            headers: {
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + token
            }
        });

        if (!response.ok) return;

        const result = await response.json();
        if (!result.success || !result.data) return;

        // Hydrate sidebar username from whichever identity is active.
        const user = result.data.user;
        const wallet = result.data.wallet;

        const usernameEl = document.getElementById('sidebar-username');
        if (usernameEl) {
            if (user?.username) {
                usernameEl.textContent = user.username;
            } else if (wallet?.fingerprint) {
                usernameEl.textContent = wallet.fingerprint.slice(0, 12) + '…';
            }
        }

        // Show the Admin Panel link for users with the admin flag.
        if (user?.admin === true) {
            const adminLink = document.getElementById('admin-panel-link');
            if (adminLink) {
                adminLink.href = "{{ route('admin.dashboard') }}";
                adminLink.style.display = 'inline-flex';
            }
        }
    } catch (e) {
        // Non-fatal — layout hydration is best-effort.
    }
})();
</script>

<!-- 💡 NEW: JavaScript Execution Engine to manage secure API token destruction -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const logoutForm = document.getElementById('sidebar-logout-form');
    if (logoutForm) {
        logoutForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const token = localStorage.getItem('auth_token');
            
            if (!token) {
                localStorage.removeItem('auth_token');
                window.location.href = "{{ route('wallet.view.generate') }}";
                return;
            }

            try {
                // Call your unified api.php logout route matrix
                await fetch("{{ url('api/v1/logout') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + token
                    }
                });
            } catch (error) {
                console.error('API backend token deletion failure context:', error);
            } finally {
                // Wipe local properties and snap the user back to the entry panel setup layout
                localStorage.removeItem('auth_token');
                window.location.href = "{{ route('wallet.view.generate') }}";
            }
        });
    }
});
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.1/umd/popper.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.min.js"></script>

@stack('scripts')
</body>
</html>