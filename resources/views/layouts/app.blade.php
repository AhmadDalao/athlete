<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Throughline' }} · {{ $platformSettings['app_name'] ?? 'Throughline' }}</title>
    @vite('resources/js/app.js')
    @livewireStyles
</head>
<body>
@php($user = auth()->user())
<div class="tl-mobile-bar d-lg-none">
    <a class="tl-mobile-brand" href="{{ $user?->landingPath() ?? route('home') }}">
        <span class="tl-mark"><i class="fa-solid fa-route"></i></span>
        <span>
            <span class="d-block fw-bold">{{ $platformSettings['app_name'] ?? 'Throughline' }}</span>
            <small class="tl-muted">{{ str($user?->role ?? 'app')->headline() }}</small>
        </span>
    </a>
    <button class="tl-mobile-menu" type="button" data-bs-toggle="offcanvas" data-bs-target="#tlSidebar" aria-controls="tlSidebar">
        <i class="fa-solid fa-bars"></i>
        <span>Menu</span>
    </button>
</div>

<div class="tl-shell">
    <aside class="tl-sidebar offcanvas-lg offcanvas-start" id="tlSidebar" tabindex="-1" aria-labelledby="tlSidebarLabel">
        <div class="tl-sidebar-inner">
            <div class="d-flex d-lg-none align-items-center justify-content-between gap-3 mb-3">
                <div>
                    <div class="tl-eyebrow">Navigation</div>
                    <div class="fw-bold" id="tlSidebarLabel">{{ $platformSettings['app_name'] ?? 'Throughline' }}</div>
                </div>
                <button class="btn btn-outline-tl btn-sm" type="button" data-bs-dismiss="offcanvas" data-bs-target="#tlSidebar" aria-label="Close menu">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <a class="tl-brand d-none d-lg-flex" href="{{ $user?->landingPath() ?? route('home') }}">
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
                @can('admin.contacts')
                    <a class="tl-nav-link {{ request()->routeIs('admin.contact-submissions') ? 'active' : '' }}" href="{{ route('admin.contact-submissions') }}"><i class="fa-solid fa-inbox"></i> Contact inbox</a>
                @endcan
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
        </div>
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
            <div class="alert alert-success alert-dismissible border-0" data-tl-auto-dismiss role="status">
                {{ session('status') }}
                <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{ $slot }}
    </main>
</div>

<nav class="tl-bottom-nav d-lg-none" aria-label="Mobile primary navigation">
    @if($user?->isAdmin())
        <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="fa-solid fa-gauge"></i><span>Home</span></a>
        <a class="{{ request()->routeIs('admin.users') ? 'active' : '' }}" href="{{ route('admin.users') }}"><i class="fa-solid fa-users"></i><span>Users</span></a>
        <a class="{{ request()->routeIs('admin.settings') ? 'active' : '' }}" href="{{ route('admin.settings') }}"><i class="fa-solid fa-sliders"></i><span>Settings</span></a>
        <a class="{{ request()->routeIs('admin.audit') ? 'active' : '' }}" href="{{ route('admin.audit') }}"><i class="fa-solid fa-clipboard-list"></i><span>Logs</span></a>
    @elseif($user?->isCoach())
        <a class="{{ request()->routeIs('coach.home') ? 'active' : '' }}" href="{{ route('coach.home') }}"><i class="fa-solid fa-house"></i><span>Home</span></a>
        <a class="{{ request()->routeIs('coach.athletes') ? 'active' : '' }}" href="{{ route('coach.athletes') }}"><i class="fa-solid fa-users-line"></i><span>Athletes</span></a>
        <a class="{{ request()->routeIs('coach.programs*') ? 'active' : '' }}" href="{{ route('coach.programs') }}"><i class="fa-solid fa-dumbbell"></i><span>Programs</span></a>
        <a class="{{ request()->routeIs('coach.invitations') ? 'active' : '' }}" href="{{ route('coach.invitations') }}"><i class="fa-solid fa-paper-plane"></i><span>Invites</span></a>
    @elseif($user?->isAthlete())
        <a class="{{ request()->routeIs('app.home') ? 'active' : '' }}" href="{{ route('app.home') }}"><i class="fa-solid fa-calendar-days"></i><span>App</span></a>
        <a class="{{ request()->routeIs('app.progress') ? 'active' : '' }}" href="{{ route('app.progress') }}"><i class="fa-solid fa-chart-line"></i><span>Progress</span></a>
        <a class="{{ request()->routeIs('app.programs*') ? 'active' : '' }}" href="{{ route('app.home') }}"><i class="fa-solid fa-dumbbell"></i><span>Programs</span></a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"><i class="fa-solid fa-user"></i><span>Logout</span></button>
        </form>
@endif
</nav>
@livewireScripts
</body>
</html>
