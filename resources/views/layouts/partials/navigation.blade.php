@php
    $role = $user->isAdmin() ? 'admin' : ($user->isCoach() ? 'coach' : 'athlete');
    $groups = match ($role) {
        'admin' => [
            'Overview' => [
                ['route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'fa-table-cells-large', 'label' => 'Dashboard'],
                ['route' => 'admin.organizations', 'active' => 'admin.organizations*', 'icon' => 'fa-building-shield', 'label' => 'Organizations', 'permission' => 'organizations.manage'],
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
                ['route' => 'coach.exercises', 'active' => 'coach.exercises*', 'icon' => 'fa-list-check', 'label' => 'Exercises', 'permission' => 'exercises.manage'],
                ['route' => 'coach.schedule', 'active' => 'coach.schedule*', 'icon' => 'fa-calendar-days', 'label' => 'Schedule', 'permission' => 'schedule.manage'],
                ['route' => 'coach.reports', 'active' => 'coach.reports*', 'icon' => 'fa-chart-column', 'label' => 'Reports', 'permission' => 'reports.view'],
                ['route' => 'coach.messages', 'active' => 'coach.messages*', 'icon' => 'fa-comments', 'label' => 'Messages', 'permission' => 'messages.read'],
                ['route' => 'coach.invitations', 'active' => 'coach.invitations*', 'icon' => 'fa-user-plus', 'label' => 'Invitations'],
            ],
        ],
        default => [
            'Training' => [
                ['route' => 'app.home', 'active' => 'app.home', 'icon' => 'fa-house', 'label' => 'Home'],
                ['route' => 'app.home', 'active' => 'app.programs*', 'icon' => 'fa-dumbbell', 'label' => 'Programs', 'fragment' => 'programs'],
                ['route' => 'app.home', 'active' => 'never', 'icon' => 'fa-calendar-days', 'label' => 'Calendar', 'fragment' => 'calendar'],
                ['route' => 'app.progress', 'active' => 'app.progress*', 'icon' => 'fa-chart-line', 'label' => 'Progress'],
                ['route' => 'app.messages', 'active' => 'app.messages*', 'icon' => 'fa-comments', 'label' => 'Messages', 'permission' => 'messages.read'],
            ],
        ],
    };
@endphp

@foreach($groups as $group => $items)
    <div class="tl-nav-title">{{ $group }}</div>
    <nav class="tl-nav-group" aria-label="{{ $group }}">
        @foreach($items as $item)
            @continue(isset($item['permission']) && ! $user->can($item['permission']))
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
