<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Zeltechnologies — Weekly Reports')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon" />

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@400;500;600&family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

    {{-- Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

    {{-- App styles --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}" />

    @stack('styles')
</head>
<body>

{{-- ── Sidebar ── --}}
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="app-name">Zeltechnologies</div>
        <div class="app-label">Weekly Reports</div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">General</div>
        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid"></i> Dashboard
        </a>
        <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-text"></i> Report
        </a>
        <a href="{{ route('leave.index') }}" class="nav-item {{ request()->routeIs('leave.*') ? 'active' : '' }}">
            <i class="bi bi-calendar-check"></i> Leave
        </a>

        @auth
            @if(auth()->user()->isAdmin())
                <div class="nav-section-label">Admin</div>
                <a href="{{ route('admin.reports.index') }}" class="nav-item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> All Reports
                </a>
                @if(auth()->user()->hasPermission('manage-leave-requests'))
                    <a href="{{ route('admin.leave.index') }}" class="nav-item {{ request()->routeIs('admin.leave.*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-check"></i> Leave Requests
                    </a>
                @endif
            @endif
        @endauth
    </nav>

    <div class="sidebar-footer">
        @auth
            <div class="sidebar-user">
                <i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name }}
            </div>
            <span class="text-faint">{{ auth()->user()->isAdmin() ? 'Administrator' : 'Staff' }}</span>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm btn-block">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
            </form>
        @endauth
    </div>
</aside>

{{-- ── Main ── --}}
<div class="main-wrap">
    <div class="topbar">
        <div class="topbar-title">@yield('page-eyebrow', 'Weekly Report Portal')</div>
        <div class="topbar-actions">
            @yield('topbar-actions')
        </div>
    </div>

    <div class="page-content">
        @yield('content')
    </div>

    <div class="app-footer">
        &copy; {{ date('Y') }} Zeltechnologies. All rights reserved.
    </div>
</div>

{{-- TipTap via CDN (UMD build) --}}
<script src="https://cdn.jsdelivr.net/npm/@tiptap/core@2.4.0/dist/index.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/starter-kit@2.4.0/dist/index.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/extension-underline@2.4.0/dist/index.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/extension-text-align@2.4.0/dist/index.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/extension-highlight@2.4.0/dist/index.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/extension-link@2.4.0/dist/index.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/extension-color@2.4.0/dist/index.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/extension-text-style@2.4.0/dist/index.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/extension-subscript@2.4.0/dist/index.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/extension-superscript@2.4.0/dist/index.umd.min.js"></script>

@stack('scripts')
</body>
</html>
