@php
    $role = $user->isAdmin() ? 'admin' : ($user->isCoach() ? 'coach' : 'athlete');
    $groups = match ($role) {
        'admin' => [
            'Overview' => [
                ['route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'fa-table-cells-large', 'label' => 'Dashboard'],
            ],
            'People' => [
                ['route' => 'admin.users', 'active' => 'admin.users*', 'icon' => 'fa-users', 'label' => 'All users'],
                ['route' => 'admin.coaches', 'active' => 'admin.coaches*', 'icon' => 'fa-user-tie', 'label' => 'Coaches'],
                ['route' => 'admin.athletes', 'active' => 'admin.athletes*', 'icon' => 'fa-person-running', 'label' => 'Athletes'],
                ['route' => 'admin.invitations', 'active' => 'admin.invitations*', 'icon' => 'fa-paper-plane', 'label' => 'Invitations'],
            ],
            'Control' => [
                ['route' => 'admin.permissions', 'active' => 'admin.permissions*', 'icon' => 'fa-shield-halved', 'label' => 'Permissions'],
                ['route' => 'admin.settings', 'active' => 'admin.settings*', 'icon' => 'fa-sliders', 'label' => 'System settings'],
                ['route' => 'admin.audit', 'active' => 'admin.audit*', 'icon' => 'fa-clock-rotate-left', 'label' => 'Audit log'],
            ],
        ],
        'coach' => [
            'Workspace' => [
                ['route' => 'coach.home', 'active' => 'coach.home', 'icon' => 'fa-house', 'label' => 'Home'],
                ['route' => 'coach.athletes', 'active' => 'coach.athletes*', 'icon' => 'fa-users-line', 'label' => 'Roster'],
                ['route' => 'coach.programs', 'active' => 'coach.programs*', 'icon' => 'fa-dumbbell', 'label' => 'Programs'],
                ['route' => 'coach.invitations', 'active' => 'coach.invitations*', 'icon' => 'fa-user-plus', 'label' => 'Invitations'],
            ],
        ],
        default => [
            'Training' => [
                ['route' => 'app.home', 'active' => 'app.home', 'icon' => 'fa-house', 'label' => 'Home'],
                ['route' => 'app.home', 'active' => 'app.programs*', 'icon' => 'fa-dumbbell', 'label' => 'Programs', 'fragment' => 'programs'],
                ['route' => 'app.home', 'active' => 'never', 'icon' => 'fa-calendar-days', 'label' => 'Calendar', 'fragment' => 'calendar'],
                ['route' => 'app.progress', 'active' => 'app.progress*', 'icon' => 'fa-chart-line', 'label' => 'Progress'],
            ],
        ],
    };
@endphp

@foreach($groups as $group => $items)
    <div class="tl-nav-title">{{ $group }}</div>
    <nav class="tl-nav-group" aria-label="{{ $group }}">
        @foreach($items as $item)
            <a class="tl-nav-link {{ request()->routeIs($item['active']) ? 'active' : '' }}" href="{{ route($item['route']).(isset($item['fragment']) ? '#'.$item['fragment'] : '') }}">
                <span class="tl-nav-icon"><i class="fa-solid {{ $item['icon'] }}"></i></span>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
@endforeach

@if($user->isAdmin() && $user->can('admin.contacts'))
    <a class="tl-nav-link {{ request()->routeIs('admin.contact-submissions*') ? 'active' : '' }}" href="{{ route('admin.contact-submissions') }}">
        <span class="tl-nav-icon"><i class="fa-solid fa-inbox"></i></span>
        <span>Contact inbox</span>
    </a>
@endif
