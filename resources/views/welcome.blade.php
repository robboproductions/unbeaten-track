<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @include('partials.favicon')

        <title>{{ config('app.name', 'Unbeaten Track') }}</title>

        @fonts

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
                *,
                *::before,
                *::after {
                    box-sizing: border-box;
                }
                body {
                    margin: 0;
                    min-height: 100vh;
                    display: flex;
                    flex-direction: column;
                    font-family: ui-sans-serif, system-ui, sans-serif;
                    background: #f4f5f2;
                    color: #2a2e28;
                    -webkit-font-smoothing: antialiased;
                }
                .home-admin {
                    position: fixed;
                    top: 1.5rem;
                    right: 1.5rem;
                    z-index: 10;
                    font-size: 0.875rem;
                    font-weight: 500;
                    color: #2a2e28;
                    text-decoration: none;
                    padding: 0.375rem 1rem;
                    border: 1px solid #b8ddc8;
                    border-radius: 6px;
                    background: #fff;
                }
                .home-admin:hover {
                    border-color: #2d6a4f;
                    color: #1e3d28;
                }
                .home-main {
                    flex: 1;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    padding: 2rem 1.5rem 5rem;
                    text-align: center;
                }
                .home-logo {
                    max-width: min(280px, 80vw);
                    height: auto;
                }
                .home-wordmark {
                    font-size: clamp(1.5rem, 5vw, 2rem);
                    font-weight: 600;
                    letter-spacing: -0.02em;
                    margin: 0 0 0.5rem;
                }
                .home-tagline {
                    margin: 0;
                    font-size: 0.9375rem;
                    color: #7a8578;
                    letter-spacing: 0.12em;
                    text-transform: uppercase;
                }
            </style>
        @endif
    </head>
    <body
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            class="min-h-screen flex flex-col antialiased bg-[var(--color-river-stone)] text-[var(--color-near-black)]"
        @endif
    >
        @if (Route::has('admin.home'))
            <a
                href="{{ route('admin.home') }}"
                @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
                    class="fixed top-6 right-6 z-10 text-sm font-medium text-[var(--color-near-black)] px-4 py-1.5 rounded-[var(--radius-md)] border border-[var(--color-green-border)] bg-[var(--color-white)] hover:border-[var(--color-mid-green)] hover:text-[var(--color-deep-forest)] transition-colors"
                @else
                    class="home-admin"
                @endif
            >
                Admin
            </a>
        @endif

        <main
            @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
                class="flex flex-1 flex-col items-center justify-center px-6 pb-24 text-center gap-6"
            @else
                class="home-main"
            @endif
        >
            @if (file_exists(public_path('images/logo-dark.png')))
                <img
                    src="{{ asset('images/logo-dark.png') }}"
                    alt="{{ config('app.name', 'Unbeaten Track') }}"
                    width="280"
                    decoding="async"
                    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
                        class="w-full max-w-[min(280px,80vw)] h-auto"
                    @else
                        class="home-logo"
                    @endif
                />
            @elseif (file_exists(public_path('images/logo-light.png')))
                <img
                    src="{{ asset('images/logo-light.png') }}"
                    alt="{{ config('app.name', 'Unbeaten Track') }}"
                    width="280"
                    decoding="async"
                    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
                        class="w-full max-w-[min(280px,80vw)] h-auto"
                    @else
                        class="home-logo"
                    @endif
                />
            @else
                <p
                    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
                        class="text-2xl sm:text-3xl font-semibold tracking-tight text-[var(--color-deep-forest)] m-0"
                    @else
                        class="home-wordmark"
                    @endif
                >
                    {{ config('app.name', 'Unbeaten Track') }}
                </p>
            @endif

            <p
                @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
                    class="m-0 text-sm sm:text-base font-medium uppercase tracking-[0.12em] text-[var(--color-mid-grey)]"
                @else
                    class="home-tagline"
                @endif
            >
                Coming soon
            </p>
        </main>
    </body>
</html>
