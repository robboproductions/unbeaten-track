{{-- Right column: map + photos. $town may be null on create. --}}
@php
    /** @var \App\Models\Town|null $town */
    /** @var string $mainFormId */
    /** @var array{enabled: bool, styleUrl: string|null, proxyUrl: string|null, geocodeUrl: string|null, initialLat: float|null, initialLng: float|null, defaultZoom: int, revert?: array<string, string>} $adminMap */
    $adminMap = $adminMap ?? [
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
            'population_approx' => '',
            'status' => 'draft',
            'verification_status' => 'unverified',
        ],
    ];
@endphp

<script>
    window.__UT_ADMIN_TOWN_MAP = @json($adminMap);
</script>

<aside class="town-form-side">
    @include('admin.towns._town_publication_card', ['town' => $town, 'mainFormId' => $mainFormId])

    <div class="town-map-panel">
        <div class="town-map-panel-title">Location map</div>
        @if ($adminMap['enabled'])
            <div id="town-admin-map" class="town-map-container" role="region" aria-label="Town location map preview"></div>
        @else
            <p class="town-map-unconfigured">
                Set <code style="font-size:11px;">MAPTILER_API_KEY</code> in <code style="font-size:11px;">.env</code> (see <code style="font-size:11px;">MAPTILER_MAP_STYLE</code> and <code style="font-size:11px;">MAPTILER_HTTP_REFERER</code>) to load MapLibre tiles via the Laravel proxy. Keys are never sent to the browser.
            </p>
        @endif
    </div>

    @include('admin.towns._photo_panel', ['town' => $town, 'mainFormId' => $mainFormId])

    @includeWhen($adminMap['enabled'], 'admin.towns._town_map_assets', ['adminMap' => $adminMap])

    <script defer src="{{ asset('js/admin-town-map-init.js') }}?v=7"></script>
</aside>
