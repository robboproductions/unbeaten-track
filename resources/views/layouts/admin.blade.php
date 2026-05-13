<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @include('partials.favicon')

        <title>@yield('title', 'Admin · ' . config('app.name', 'Unbeaten Track'))</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">

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
    <body class="admin-body" x-data="{ adminMenuOpen: false }" @keydown.escape.window="adminMenuOpen = false">
        <div class="admin-sidebar-backdrop" x-show="adminMenuOpen" x-transition.opacity @click="adminMenuOpen = false" x-cloak></div>

        <div class="admin-shell">
            <aside class="admin-sidebar" :class="{ 'admin-sidebar--open': adminMenuOpen }" @click.outside="if (window.matchMedia('(max-width: 1023px)').matches) adminMenuOpen = false">
                <div class="admin-sidebar-brand">
                    <a class="admin-sidebar-brand-link" href="{{ route('admin.dashboard') }}" @click="adminMenuOpen = false">
                        @if (file_exists(public_path('images/logo-light.png')))
                            <img class="admin-sidebar-brand-logo" src="{{ asset('images/logo-light.png') }}" alt="{{ config('app.name', 'Unbeaten Track') }}" width="172" decoding="async" />
                        @else
                            <span class="admin-sidebar-brand-fallback">
                                <span class="admin-sidebar-logo-dot" aria-hidden="true"></span>
                                <span class="admin-sidebar-logo-name">Unbeaten Track</span>
                            </span>
                        @endif
                    </a>
                    <span class="admin-sidebar-brand-app">Admin</span>
                </div>

                <div class="admin-sidebar-section">
                    <div class="admin-sidebar-section-label">Overview</div>
                    <a class="admin-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}" @click="adminMenuOpen = false">
                        Dashboard
                    </a>
                    <span class="admin-nav-item disabled">Statistics</span>
                </div>

                <div class="admin-sidebar-section">
                    <div class="admin-sidebar-section-label">Content</div>
                    <a class="admin-nav-item {{ request()->routeIs('admin.towns.*') ? 'active' : '' }}" href="{{ route('admin.towns.index') }}" @click="adminMenuOpen = false">
                        Towns
                    </a>
                    <a class="admin-nav-item {{ request()->routeIs('admin.pois.*') ? 'active' : '' }}" href="{{ route('admin.pois.index') }}" @click="adminMenuOpen = false">
                        POIs
                    </a>
                    <a class="admin-nav-item {{ request()->routeIs('admin.narrations.*') ? 'active' : '' }}" href="{{ route('admin.narrations.index') }}" @click="adminMenuOpen = false">
                        Narrations
                    </a>
                    <span class="admin-nav-item disabled">Media library</span>
                </div>

                <div class="admin-sidebar-section">
                    <div class="admin-sidebar-section-label">Account</div>
                    <a class="admin-nav-item {{ request()->routeIs('admin.profile.*') ? 'active' : '' }}" href="{{ route('admin.profile.edit') }}" @click="adminMenuOpen = false">
                        Your profile
                    </a>
                </div>

                <div class="admin-sidebar-section">
                    <div class="admin-sidebar-section-label">System</div>
                    @can('superAdmin')
                        <a class="admin-nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}" @click="adminMenuOpen = false">
                            Users
                        </a>
                    @else
                        <span class="admin-nav-item disabled">Users</span>
                    @endcan
                    <span class="admin-nav-item disabled">Categories</span>
                    <span class="admin-nav-item disabled">Journeys</span>
                    <span class="admin-nav-item disabled">Settings</span>
                </div>

                <div class="admin-sidebar-footer">
                    <div class="admin-sidebar-user">
                        <div class="admin-user-avatar" aria-hidden="true">{{ auth()->user()->initials() }}</div>
                        <div class="admin-sidebar-user-text">
                            <div class="admin-user-name">{{ auth()->user()->name }}</div>
                            <div class="admin-user-role">{{ auth()->user()->roleLabel() }}</div>
                        </div>
                    </div>
                    <form method="post" action="{{ route('logout') }}" class="admin-sidebar-logout-form">
                        @csrf
                        <button type="submit" class="btn btn-neutral btn-sm admin-sidebar-logout">Sign out</button>
                    </form>
                </div>
            </aside>

            <div class="admin-main">
                <div class="admin-mobile-bar">
                    <span class="admin-menu-toggle" @click="adminMenuOpen = !adminMenuOpen" role="presentation">Menu</span>
                    @if (file_exists(public_path('images/logo-light.png')))
                        <img class="admin-mobile-logo" src="{{ asset('images/logo-light.png') }}" alt="" width="130" decoding="async" aria-hidden="true" />
                    @endif
                    <span class="admin-mobile-title">Unbeaten Track</span>
                </div>
                @yield('content')
            </div>
        </div>
        @stack('scripts')
    </body>
</html>
