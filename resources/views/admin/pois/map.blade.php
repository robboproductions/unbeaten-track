@extends('layouts.admin')

@section('title', 'POIs map · Admin')

@section('content')
    @php
        /** @var array<string, mixed> $mapBrowse */
    @endphp
    @include('admin.towns._town_map_assets')
    <div class="admin-page-header">
        <div>
            <div class="admin-page-title">POIs map</div>
            <div class="admin-page-subtitle">
                {{ $mappedPoiCount }} on map
                @if ($totalPoiRows !== $mappedPoiCount)
                    · {{ $totalPoiRows - $mappedPoiCount }} without coordinates
                @endif
            </div>
        </div>
        <div class="admin-page-actions">
            <a class="btn btn-neutral btn-sm" href="{{ route('admin.pois.index', request()->query()) }}">List view</a>
            <a class="btn btn-primary btn-sm" href="{{ route('admin.pois.create') }}">+ Add POI</a>
        </div>
    </div>

    <div class="admin-content towns-map-browse-content">
        <div class="card towns-map-browse-card">
            <div class="towns-map-browse-toolbar">
                <form method="get" action="{{ route('admin.pois.map') }}" class="towns-map-browse-filters">
                    <input
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search POIs…"
                        class="towns-map-browse-input"
                    />
                    <select name="state" class="towns-map-browse-select">
                        <option value="">All states</option>
                        @foreach ($stateOptions as $st)
                            <option value="{{ $st }}" @selected(request('state') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                    <select name="category" class="towns-map-browse-select">
                        <option value="">All categories</option>
                        @foreach ($categoryOptions as $cat)
                            <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="towns-map-browse-select">
                        <option value="">All status</option>
                        <option value="published" @selected(request('status') === 'published')>Published</option>
                        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    </select>
                    <select name="verification_status" class="towns-map-browse-select">
                        <option value="">All verification</option>
                        @foreach (\App\Enums\PoiVerificationStatus::cases() as $vs)
                            <option value="{{ $vs->value }}" @selected(request('verification_status') === $vs->value)>{{ $vs->label() }}</option>
                        @endforeach
                    </select>
                    <input class="btn btn-neutral btn-sm" type="submit" value="Filter" />
                    <a class="btn btn-neutral btn-sm" href="{{ route('admin.pois.map') }}">Reset</a>
                </form>
            </div>

            @if (! ($mapBrowse['enabled'] ?? false))
                <div class="towns-map-browse-disabled">
                    <p>Set <code>MAPTILER_API_KEY</code> in <code>.env</code> to load the map (same as town edit preview).</p>
                </div>
            @else
                <p id="pois-map-load-error" class="towns-map-browse-error" hidden></p>
                <div id="pois-admin-map-browse" class="towns-admin-map-browse" role="region" aria-label="POIs map"></div>
            @endif
        </div>
    </div>

    <script>
        window.__UT_ADMIN_POIS_MAP_BROWSE = @json($mapBrowse);
    </script>
    <script defer src="{{ asset('js/admin-pois-map-browse.js') }}?v=1"></script>
@endsection
