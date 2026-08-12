@php($themePreference = auth()->user()?->theme_preference ?? ($platformSettings['default_theme'] ?? 'system'))
<!doctype html>
<html
    lang="en"
    data-theme-preference="{{ $themePreference }}"
    @auth data-theme-endpoint="{{ route('appearance.update') }}" @endauth
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#090d0b">
    <title>{{ $title ?? ($platformSettings['app_name'] ?? 'Throughline') }}</title>
    <script>
        (() => {
            const server = @json($themePreference);
            const preference = server === 'system' ? (localStorage.getItem('throughline-theme') || server) : server;
            document.documentElement.dataset.themePreference = preference;
            document.documentElement.dataset.theme = preference === 'system'
                ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : preference;
        })();
    </script>
    @vite('resources/js/app.js')
    @livewireStyles
</head>
<body class="tl-public">
    <header class="tl-public-header">
        <div class="container tl-public-nav">
            <x-tl.brand :href="route('home')" :show-tagline="false" />
            <nav class="tl-public-links d-none d-lg-flex" aria-label="Public navigation">
                @if($platformSettings['public_features_enabled'])<a class="{{ request()->routeIs('features') ? 'active' : '' }}" href="{{ route('features') }}">Features</a>@endif
                @if($platformSettings['public_pricing_enabled'])<a class="{{ request()->routeIs('pricing') ? 'active' : '' }}" href="{{ route('pricing') }}">Pricing</a>@endif
                @if($platformSettings['public_contact_enabled'])<a class="{{ request()->routeIs('contact') ? 'active' : '' }}" href="{{ route('contact') }}">Contact</a>@endif
            </nav>
            <div class="tl-public-actions">
                <x-tl.theme-switch compact />
                @auth
                    <a class="btn btn-tl d-none d-sm-inline-flex" href="{{ auth()->user()->landingPath() }}">Open workspace</a>
                @else
                    <a class="btn btn-ghost d-none d-sm-inline-flex" href="{{ route('login') }}">Log in</a>
                    @if($platformSettings['request_access_enabled'] && $platformSettings['public_contact_enabled'])<a class="btn btn-tl d-none d-md-inline-flex" href="{{ route('contact') }}">Request access</a>@endif
                @endauth
                <button class="tl-icon-button d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#publicNavigation" aria-label="Open menu">
                    <i class="fa-solid fa-bars-staggered"></i>
                </button>
            </div>
        </div>
    </header>

    <div class="offcanvas offcanvas-end tl-public-drawer" tabindex="-1" id="publicNavigation" aria-labelledby="publicNavigationTitle">
        <div class="offcanvas-header">
            <x-tl.brand :href="route('home')" compact :show-tagline="false" />
            <button class="tl-icon-button" type="button" data-bs-dismiss="offcanvas" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="offcanvas-body">
            <nav class="tl-drawer-links">
                @if($platformSettings['public_features_enabled'])<a href="{{ route('features') }}"><i class="fa-solid fa-table-cells-large"></i> Features</a>@endif
                @if($platformSettings['public_pricing_enabled'])<a href="{{ route('pricing') }}"><i class="fa-solid fa-tags"></i> Pricing</a>@endif
                @if($platformSettings['public_contact_enabled'])<a href="{{ route('contact') }}"><i class="fa-solid fa-message"></i> Contact</a>@endif
                <a href="{{ route('login') }}"><i class="fa-solid fa-arrow-right-to-bracket"></i> Log in</a>
            </nav>
        </div>
    </div>

    <main>
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <footer class="tl-public-footer">
        <div class="container">
            <div class="row g-4 align-items-start">
                <div class="col-lg-5">
                    <x-tl.brand :href="route('home')" />
                    <p>{{ $platformSettings['footer_copy'] }}</p>
                </div>
                <div class="col-6 col-lg-2">
                    <strong>Product</strong>
                    @if($platformSettings['public_features_enabled'])<a href="{{ route('features') }}">Features</a>@endif
                    @if($platformSettings['public_pricing_enabled'])<a href="{{ route('pricing') }}">Pricing</a>@endif
                </div>
                <div class="col-6 col-lg-2">
                    <strong>Company</strong>
                    @if($platformSettings['public_contact_enabled'])<a href="{{ route('contact') }}">Contact</a>@endif
                    <a href="{{ route('login') }}">Login</a>
                    @if($platformSettings['terms_url'])<a href="{{ $platformSettings['terms_url'] }}" target="_blank" rel="noopener">Terms</a>@endif
                    @if($platformSettings['privacy_url'])<a href="{{ $platformSettings['privacy_url'] }}" target="_blank" rel="noopener">Privacy</a>@endif
                </div>
                <div class="col-lg-3">
                    <strong>Ready to simplify coaching?</strong>
                    @if($platformSettings['request_access_enabled'] && $platformSettings['public_contact_enabled'])<a class="btn btn-tl mt-3" href="{{ route('contact') }}">{{ $platformSettings['homepage_primary_label'] }}</a>@endif
                </div>
            </div>
            <div class="tl-footer-base"><span>© {{ now()->year }} {{ $platformSettings['app_name'] }}</span><span>{{ $platformSettings['support_email'] }}@if($platformSettings['support_phone']) · {{ $platformSettings['support_phone'] }}@endif</span></div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
