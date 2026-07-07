<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? ($platformSettings['app_name'] ?? 'Throughline') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/throughline.css') }}" rel="stylesheet">
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
