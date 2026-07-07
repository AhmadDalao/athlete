<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Throughline' }} · {{ $platformSettings['app_name'] ?? 'Throughline' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/throughline.css') }}" rel="stylesheet">
    @livewireStyles
</head>
<body>
@php($user = auth()->user())
<div class="tl-shell">
    <aside class="tl-sidebar">
        <a class="tl-brand" href="{{ $user?->landingPath() ?? route('home') }}">
            <span class="tl-mark"><i class="fa-solid fa-route"></i></span>
            <span>
                <span class="d-block fw-bold">{{ $platformSettings['app_name'] ?? 'Throughline' }}</span>
                <small class="tl-muted">{{ $platformSettings['tagline'] ?? 'Coach performance OS' }}</small>
            </span>
        </a>

        @if($user?->isAdmin())
            <div class="tl-nav-title">Admin</div>
            <a class="tl-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a class="tl-nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}" href="{{ route('admin.users') }}"><i class="fa-solid fa-users"></i> Users</a>
            <a class="tl-nav-link {{ request()->routeIs('admin.coaches') ? 'active' : '' }}" href="{{ route('admin.coaches') }}"><i class="fa-solid fa-user-tie"></i> Coaches</a>
            <a class="tl-nav-link {{ request()->routeIs('admin.athletes') ? 'active' : '' }}" href="{{ route('admin.athletes') }}"><i class="fa-solid fa-person-running"></i> Athletes</a>
            <a class="tl-nav-link {{ request()->routeIs('admin.invitations') ? 'active' : '' }}" href="{{ route('admin.invitations') }}"><i class="fa-solid fa-envelope-open-text"></i> Invitations</a>
            <a class="tl-nav-link {{ request()->routeIs('admin.permissions') ? 'active' : '' }}" href="{{ route('admin.permissions') }}"><i class="fa-solid fa-key"></i> Permissions</a>
            <a class="tl-nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}" href="{{ route('admin.settings') }}"><i class="fa-solid fa-sliders"></i> Settings</a>
            <a class="tl-nav-link {{ request()->routeIs('admin.audit') ? 'active' : '' }}" href="{{ route('admin.audit') }}"><i class="fa-solid fa-clipboard-list"></i> Logs</a>
        @endif

        @if($user?->isCoach())
            <div class="tl-nav-title">Coach</div>
            <a class="tl-nav-link {{ request()->routeIs('coach.home') ? 'active' : '' }}" href="{{ route('coach.home') }}"><i class="fa-solid fa-house"></i> Coach home</a>
            <a class="tl-nav-link {{ request()->routeIs('coach.athletes') ? 'active' : '' }}" href="{{ route('coach.athletes') }}"><i class="fa-solid fa-users-line"></i> Athletes</a>
            <a class="tl-nav-link {{ request()->routeIs('coach.programs*') ? 'active' : '' }}" href="{{ route('coach.programs') }}"><i class="fa-solid fa-dumbbell"></i> Programs</a>
            <a class="tl-nav-link {{ request()->routeIs('coach.invitations') ? 'active' : '' }}" href="{{ route('coach.invitations') }}"><i class="fa-solid fa-paper-plane"></i> Invites</a>
        @endif

        @if($user?->isAthlete())
            <div class="tl-nav-title">Athlete</div>
            <a class="tl-nav-link {{ request()->routeIs('app.home') ? 'active' : '' }}" href="{{ route('app.home') }}"><i class="fa-solid fa-calendar-days"></i> App</a>
            <a class="tl-nav-link {{ request()->routeIs('app.progress') ? 'active' : '' }}" href="{{ route('app.progress') }}"><i class="fa-solid fa-chart-line"></i> Progress</a>
        @endif

        <div class="tl-nav-title">Account</div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="tl-nav-link border-0 bg-transparent w-100" type="submit"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</button>
        </form>
    </aside>

    <main class="tl-main">
        <div class="tl-topbar">
            <div>
                <div class="tl-eyebrow">{{ strtoupper($user->role) }}</div>
                <h1 class="h3 mb-0">{{ $title ?? 'Workspace' }}</h1>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="tl-badge green">{{ $user->name }}</span>
                <span class="tl-badge gray">{{ $user->email }}</span>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success border-0">{{ session('status') }}</div>
        @endif

        {{ $slot }}
    </main>
</div>
@livewireScripts
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
