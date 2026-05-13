{{-- Right column: map + photos. $poi may be null on create. --}}
@php
    /** @var \App\Models\Poi|null $poi */
    /** @var string $mainFormId */
    /** @var array{enabled: bool, styleUrl: string|null, proxyUrl: string|null, geocodeUrl: string|null, initialLat: float|null, initialLng: float|null, defaultZoom: int, revert?: array<string, string>} $adminPoiMap */
    $adminPoiMap = $adminPoiMap ?? [
        'enabled' => false,
        'styleUrl' => null,
        'proxyUrl' => null,
        'geocodeUrl' => null,
        'initialLat' => null,
        'initialLng' => null,
        'defaultZoom' => 4,
        'revert' => [
            'latitude' => '',
            'longitude' => '',
            'status' => 'draft',
        ],
    ];
@endphp

<script>
    window.__UT_ADMIN_POI_MAP = @json($adminPoiMap);
</script>

<aside class="town-form-side">
    @include('admin.pois._poi_publication_card', ['poi' => $poi, 'mainFormId' => $mainFormId])

    <div class="town-map-panel">
        <div class="town-map-panel-title">Location map</div>
        @if ($adminPoiMap['enabled'])
            <div id="poi-admin-map" class="town-map-container" role="region" aria-label="POI location map preview"></div>
        @else
            <p class="town-map-unconfigured">
                Set <code style="font-size:11px;">MAPTILER_API_KEY</code> in <code style="font-size:11px;">.env</code> (see <code style="font-size:11px;">MAPTILER_MAP_STYLE</code> and <code style="font-size:11px;">MAPTILER_HTTP_REFERER</code>) to load MapLibre tiles via the Laravel proxy. Keys are never sent to the browser.
            </p>
        @endif
    </div>

    @include('admin.pois._poi_photo_panel', ['poi' => $poi, 'mainFormId' => $mainFormId])

    @includeWhen($adminPoiMap['enabled'], 'admin.towns._town_map_assets', ['adminMap' => $adminPoiMap])

    <script defer src="{{ asset('js/admin-poi-map-init.js') }}?v=3"></script>
</aside>
