<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? ($platformSettings['app_name'] ?? 'Throughline') }}</title>
    @vite('resources/js/app.js')
    @livewireStyles
</head>
<body>
    <nav class="navbar navbar-expand-lg py-3">
        <div class="container">
            <a class="tl-brand mb-0" href="{{ route('home') }}">
                <span class="tl-mark"><i class="fa-solid fa-route"></i></span>
                <span>
                    <span class="d-block fw-black fs-4">{{ $platformSettings['app_name'] ?? 'Throughline' }}</span>
                    <small class="tl-muted">{{ $platformSettings['tagline'] ?? 'Coach performance OS' }}</small>
                </span>
            </a>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-tl" href="{{ route('contact') }}">Contact</a>
                <a class="btn btn-tl" href="{{ route('login') }}">Login</a>
            </div>
        </div>
    </nav>

    <main>
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    @livewireScripts
</body>
</html>
