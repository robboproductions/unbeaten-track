<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @include('partials.favicon')

        <title>@yield('title', config('app.name', 'Unbeaten Track'))</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

        @if (file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <link rel="stylesheet" href="{{ asset('css/unbeaten-fallback.css') }}">
        @endif

        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </head>
    <body x-data="{ journeysOpen: false }" @keydown.escape.window="journeysOpen = false">
        <nav class="nav">
            <div class="nav-logo">
                @if (file_exists(public_path('images/logo-light.png')))
                    <img class="nav-logo-img" src="{{ asset('images/logo-light.png') }}" alt="{{ config('app.name', 'Unbeaten Track') }}" width="200" decoding="async" />
                @else
                    <span class="nav-logo-dot" aria-hidden="true"></span>
                    <span>Unbeaten Track</span>
                @endif
            </div>

            <div class="nav-links">
                <span class="nav-link active">Plan</span>

                <div class="nav-dropdown-wrap" @click.outside="journeysOpen = false">
                    <span
                        class="nav-link nav-link-dropdown"
                        @click="journeysOpen = !journeysOpen"
                        :aria-expanded="journeysOpen.toString()"
                    >
                        My Journeys
                    </span>

                    <div class="nav-dropdown" x-show="journeysOpen" x-transition x-cloak>
                        <div class="nav-dropdown-item active">
                            <span class="nav-dd-dot" style="background:#4a7c59;"></span>
                            <span class="nav-dd-name">Sydney → Melbourne</span>
                            <span class="nav-dd-meta">3 days · 740 km</span>
                            <span class="nav-dd-badge" style="background:#ddeee4;color:#1e3d28;">In progress</span>
                        </div>

                        <div class="nav-dropdown-item">
                            <span class="nav-dd-dot" style="background:#dde0da;"></span>
                            <span class="nav-dd-name">Blue Mountains loop</span>
                            <span class="nav-dd-meta">2 days · 320 km</span>
                            <span class="nav-dd-badge" style="background:#f5e8c0;color:#4a3000;">Draft</span>
                        </div>

                        <div class="nav-dropdown-item">
                            <span class="nav-dd-dot" style="background:#dde0da;"></span>
                            <span class="nav-dd-name">Snowy Mountains drive</span>
                            <span class="nav-dd-meta">4 days · 680 km</span>
                            <span class="nav-dd-badge" style="background:#d8e8f5;color:#1c3150;">Saved</span>
                        </div>

                        <div class="nav-dropdown-item">
                            <span class="nav-dd-dot" style="background:#dde0da;"></span>
                            <span class="nav-dd-name">Hunter Valley wine run</span>
                            <span class="nav-dd-meta">1 day · 280 km</span>
                            <span class="nav-dd-badge" style="background:#d8e8f5;color:#1c3150;">Saved</span>
                        </div>

                        <div class="nav-dropdown-new">+ New journey</div>
                    </div>
                </div>

                <span class="nav-link">Discover</span>
                <span class="nav-link">Map</span>
                <span class="nav-link">Library</span>
            </div>

            <div class="nav-right">
                <span class="nav-cta">+ New journey</span>
                <div class="nav-avatar">KR</div>
            </div>
        </nav>

        <main>
            @yield('content')
        </main>
    </body>
</html>

