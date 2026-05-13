@extends('layouts.admin')

@section('title', 'Towns map · Admin')

@section('content')
    @php
        /** @var array<string, mixed> $mapBrowse */
    @endphp
    @include('admin.towns._town_map_assets')
    <div class="admin-page-header">
        <div>
            <div class="admin-page-title">Towns map</div>
            <div class="admin-page-subtitle">
                {{ $mappedTownCount }} on map
                @if ($totalTownRows !== $mappedTownCount)
                    · {{ $totalTownRows - $mappedTownCount }} without coordinates
                @endif
            </div>
        </div>
        <div class="admin-page-actions">
            <a class="btn btn-neutral btn-sm" href="{{ route('admin.towns.index', request()->query()) }}">List view</a>
            <a class="btn btn-primary btn-sm" href="{{ route('admin.towns.create') }}">+ Add town</a>
        </div>
    </div>

    <div class="admin-content towns-map-browse-content">
        <div class="card towns-map-browse-card">
            <div class="towns-map-browse-toolbar">
                <form method="get" action="{{ route('admin.towns.map') }}" class="towns-map-browse-filters">
                    <input
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search towns…"
                        class="towns-map-browse-input"
                    />
                    <select name="state" class="towns-map-browse-select">
                        <option value="">All states</option>
                        @foreach ($stateOptions as $st)
                            <option value="{{ $st }}" @selected(request('state') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="towns-map-browse-select">
                        <option value="">All status</option>
                        <option value="published" @selected(request('status') === 'published')>Published</option>
                        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    </select>
                    <input class="btn btn-neutral btn-sm" type="submit" value="Filter" />
                    <a class="btn btn-neutral btn-sm" href="{{ route('admin.towns.map') }}">Reset</a>
                </form>
            </div>

            @if (! ($mapBrowse['enabled'] ?? false))
                <div class="towns-map-browse-disabled">
                    <p>Set <code>MAPTILER_API_KEY</code> in <code>.env</code> to load the map (same as town edit preview).</p>
                </div>
            @else
                <p id="towns-map-load-error" class="towns-map-browse-error" hidden></p>
                <div id="towns-admin-map-browse" class="towns-admin-map-browse" role="region" aria-label="Towns map"></div>
            @endif
        </div>
    </div>

    <script>
        window.__UT_ADMIN_TOWNS_MAP_BROWSE = @json($mapBrowse);
    </script>
    <script defer src="{{ asset('js/admin-towns-map-browse.js') }}?v=3"></script>
@endsection
