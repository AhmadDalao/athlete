@props(['compact' => false])

<div {{ $attributes->class(['dropdown', 'tl-theme-control', 'is-compact' => $compact]) }}>
    <button class="tl-icon-button dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Choose appearance">
        <i class="fa-solid fa-circle-half-stroke" data-theme-icon></i>
        @unless($compact)<span data-theme-label>System</span>@endunless
    </button>
    <div class="dropdown-menu dropdown-menu-end tl-theme-menu">
        @foreach([
            'system' => ['fa-display', 'System'],
            'dark' => ['fa-moon', 'Dark'],
            'light' => ['fa-sun', 'Light'],
        ] as $value => [$icon, $label])
            <button class="dropdown-item" type="button" data-theme-option="{{ $value }}">
                <i class="fa-solid {{ $icon }}"></i>
                <span>{{ $label }}</span>
                <i class="fa-solid fa-check ms-auto tl-theme-check"></i>
            </button>
        @endforeach
    </div>
</div>
