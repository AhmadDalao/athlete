@php
    $user = auth()->user();
    $themePreference = $user?->theme_preference ?? 'system';
    $role = $user?->isAdmin() ? 'admin' : ($user?->isCoach() ? 'coach' : 'athlete');
    $activeMembership = $user?->activeOrganizationMembership();
    $roleLabel = $activeMembership?->role
        ? str($activeMembership->role)->replace('_', ' ')->headline()
        : str($user?->role ?? 'member')->headline();
    $availableOrganizations = $user?->isPlatformAdmin()
        ? \App\Models\Organization::query()->where('status', 'active')->orderBy('name')->get()
        : $user?->organizations()->wherePivot('status', 'active')->where('organizations.status', 'active')->orderBy('name')->get();
    $activeOrganization = $availableOrganizations?->firstWhere('id', $user?->current_organization_id) ?? $availableOrganizations?->first();
@endphp
<!doctype html>
<html
    lang="en"
    data-theme-preference="{{ $themePreference }}"
    data-theme-endpoint="{{ route('appearance.update') }}"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#090d0b">
    <title>{{ $title ?? 'Workspace' }} · {{ $platformSettings['app_name'] ?? 'Throughline' }}</title>
    <script>
        (() => {
            const server = @json($themePreference);
            const preference = server === 'system' ? (localStorage.getItem('throughline-theme') || server) : server;
            const resolved = preference === 'system'
                ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : preference;
            document.documentElement.dataset.themePreference = preference;
            document.documentElement.dataset.theme = resolved;
        })();
    </script>
    @vite('resources/js/app.js')
    @livewireStyles
</head>
<body class="tl-workspace tl-role-{{ $role }}">
    <header class="tl-mobile-header d-lg-none">
        <x-tl.brand :href="$user->landingPath()" compact :show-tagline="false" />
        <div class="d-flex align-items-center gap-2">
            <x-tl.theme-switch compact />
            <button class="tl-icon-button" type="button" data-bs-toggle="offcanvas" data-bs-target="#tlSidebar" aria-controls="tlSidebar" aria-label="Open navigation">
                <i class="fa-solid fa-bars-staggered"></i>
            </button>
        </div>
    </header>

    <div class="tl-shell">
        <aside class="tl-sidebar offcanvas-lg offcanvas-start" id="tlSidebar" tabindex="-1" aria-labelledby="tlSidebarLabel">
            <div class="tl-sidebar-inner">
                <div class="tl-sidebar-head">
                    <x-tl.brand :href="$user->landingPath()" />
                    <button class="tl-icon-button d-lg-none" type="button" data-bs-dismiss="offcanvas" data-bs-target="#tlSidebar" aria-label="Close navigation">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                @if($activeOrganization)
                    <div class="tl-organization-card">
                        <span class="tl-organization-avatar">{{ str($activeOrganization->name)->substr(0, 2)->upper() }}</span>
                        <span class="min-w-0">
                            <small>Active organization</small>
                            <strong>{{ $activeOrganization->name }}</strong>
                        </span>
                        @if($availableOrganizations->count() > 1)
                            <button class="tl-icon-button ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#organizationSwitcher" aria-expanded="false" aria-label="Switch organization">
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                        @endif
                    </div>
                    @if($availableOrganizations->count() > 1)
                        <div class="collapse tl-organization-list" id="organizationSwitcher">
                            @foreach($availableOrganizations as $organization)
                                <form method="POST" action="{{ route('organizations.select', $organization) }}">
                                    @csrf
                                    <button class="{{ $organization->id === $activeOrganization->id ? 'active' : '' }}" type="submit">
                                        <span>{{ str($organization->name)->substr(0, 2)->upper() }}</span>
                                        {{ $organization->name }}
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    @endif
                @endif

                <div class="tl-sidebar-nav">
                    @include('layouts.partials.navigation', ['user' => $user])
                </div>

                <div class="tl-sidebar-account">
                    <div class="tl-user-avatar">{{ str($user->name)->substr(0, 2)->upper() }}</div>
                    <div class="min-w-0 flex-grow-1">
                        <strong class="d-block text-truncate">{{ $user->name }}</strong>
                        <small class="d-block text-truncate">{{ $roleLabel }}</small>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="tl-icon-button" type="submit" aria-label="Logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></button>
                    </form>
                </div>
            </div>
        </aside>

        <main class="tl-main">
            <header class="tl-topbar d-none d-lg-flex">
                <div class="tl-page-context">
                    <span class="tl-eyebrow">{{ $roleLabel }}</span>
                    <h1>{{ $title ?? 'Workspace' }}</h1>
                </div>
                <div class="tl-topbar-actions">
                    <label class="tl-global-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="search" placeholder="Search workspace" aria-label="Search workspace">
                        <kbd>⌘K</kbd>
                    </label>
                    <x-tl.theme-switch />
                    <button class="tl-icon-button" type="button" aria-label="Notifications"><i class="fa-regular fa-bell"></i></button>
                    <div class="tl-topbar-user">
                        <span class="tl-user-avatar">{{ str($user->name)->substr(0, 2)->upper() }}</span>
                        <span><strong>{{ $user->name }}</strong><small>{{ $roleLabel }}</small></span>
                    </div>
                </div>
            </header>

            <div class="tl-content">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible tl-alert" data-tl-auto-dismiss role="status">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>{{ session('status') }}</span>
                        <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{ $slot }}
            </div>
        </main>
    </div>

    <nav class="tl-bottom-nav d-lg-none" aria-label="Mobile primary navigation">
        @if($role === 'admin')
            <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="fa-solid fa-house"></i><span>Home</span></a>
            <a class="{{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users') }}"><i class="fa-solid fa-users"></i><span>Users</span></a>
            <a class="tl-bottom-action {{ request()->routeIs('admin.coaches*', 'admin.athletes*') ? 'active' : '' }}" href="{{ route('admin.athletes') }}"><i class="fa-solid fa-person-running"></i><span>People</span></a>
            <a class="{{ request()->routeIs('admin.audit*') ? 'active' : '' }}" href="{{ route('admin.audit') }}"><i class="fa-solid fa-clock-rotate-left"></i><span>Activity</span></a>
            <a class="{{ request()->routeIs('admin.settings*') ? 'active' : '' }}" href="{{ route('admin.settings') }}"><i class="fa-solid fa-sliders"></i><span>More</span></a>
        @elseif($role === 'coach')
            <a class="{{ request()->routeIs('coach.home') ? 'active' : '' }}" href="{{ route('coach.home') }}"><i class="fa-solid fa-house"></i><span>Home</span></a>
            <a class="{{ request()->routeIs('coach.athletes*') ? 'active' : '' }}" href="{{ route('coach.athletes') }}"><i class="fa-solid fa-users-line"></i><span>Roster</span></a>
            <a class="tl-bottom-action {{ request()->routeIs('coach.programs*') ? 'active' : '' }}" href="{{ route('coach.programs') }}"><i class="fa-solid fa-dumbbell"></i><span>Programs</span></a>
            <a class="{{ request()->routeIs('coach.messages*') ? 'active' : '' }}" href="{{ route('coach.messages') }}"><i class="fa-solid fa-comments"></i><span>Messages</span></a>
            <button type="button" data-bs-toggle="offcanvas" data-bs-target="#tlSidebar"><i class="fa-solid fa-bars"></i><span>More</span></button>
        @else
            <a class="{{ request()->routeIs('app.home') ? 'active' : '' }}" href="{{ route('app.home') }}"><i class="fa-solid fa-house"></i><span>Home</span></a>
            <a href="{{ route('app.home') }}#calendar"><i class="fa-regular fa-calendar"></i><span>Calendar</span></a>
            <a class="tl-bottom-action {{ request()->routeIs('app.workouts*') ? 'active' : '' }}" href="{{ route('app.home') }}#today"><i class="fa-solid fa-play"></i><span>Train</span></a>
            <a class="{{ request()->routeIs('app.messages*') ? 'active' : '' }}" href="{{ route('app.messages') }}"><i class="fa-solid fa-comments"></i><span>Messages</span></a>
            <button type="button" data-bs-toggle="offcanvas" data-bs-target="#tlSidebar"><i class="fa-solid fa-user"></i><span>More</span></button>
        @endif
    </nav>

    @livewireScripts
</body>
</html>
