@extends('layouts.admin')

@section('title', 'Dashboard · Admin')

@section('content')
    <div class="admin-page-header">
        <div>
            <div class="admin-page-title">Dashboard</div>
            <div class="admin-page-subtitle">Good morning — here's what's happening</div>
        </div>
        <div class="admin-page-actions">
            <span class="btn btn-neutral btn-sm">Refresh</span>
            <a class="btn btn-primary btn-sm" href="{{ route('admin.towns.create') }}">Add content</a>
        </div>
    </div>

    <div class="admin-stats-row" data-admin-stats-row>
        <button type="button" class="admin-stat-card admin-stat-card--action" data-dash-stat="towns" aria-label="Show towns on the map (NSW and Victoria)">
            <div class="admin-stat-label">Towns</div>
            <div class="admin-stat-value">{{ $stats['towns'] }}</div>
        </button>
        <button type="button" class="admin-stat-card admin-stat-card--action" data-dash-stat="pois" aria-label="Show all POIs on the map (NSW and Victoria)">
            <div class="admin-stat-label">POIs</div>
            <div class="admin-stat-value">{{ $stats['pois'] }}</div>
        </button>
        <button type="button" class="admin-stat-card admin-stat-card--action" data-dash-stat="pois-pending" aria-label="Show pending POIs on the map">
            <div class="admin-stat-label">Pending review</div>
            <div class="admin-stat-value">{{ $stats['pendingPois'] }}</div>
        </button>
        <button type="button" class="admin-stat-card admin-stat-card--action" data-dash-stat="towns-pending" aria-label="Show pending towns on the map (NSW and Victoria)">
            <div class="admin-stat-label">Towns pending review</div>
            <div class="admin-stat-value">{{ $stats['pendingTowns'] }}</div>
        </button>
        <button type="button" class="admin-stat-card admin-stat-card--action" data-dash-stat="pois-published" aria-label="Show published POIs on the map">
            <div class="admin-stat-label">Published POIs</div>
            <div class="admin-stat-value">{{ $stats['publishedPois'] }}</div>
        </button>
        <button type="button" class="admin-stat-card admin-stat-card--action" data-dash-stat="towns-published" aria-label="Show published towns on the map (NSW and Victoria)">
            <div class="admin-stat-label">Published Towns</div>
            <div class="admin-stat-value">{{ $stats['publishedTowns'] }}</div>
        </button>
    </div>

    <div id="admin-dashboard-overview" class="admin-content admin-dashboard-overview-wrap">
        <div class="card admin-dashboard-overview-card">
            <div class="card-header">
                <div>
                    <div class="card-title">NSW &amp; Victoria content map</div>
                    <div class="card-subtitle">
                        POIs on by default; turn towns on to compare coverage. Filters apply to the map only (NSW &amp; VIC records with coordinates).
                        <span class="admin-dashboard-overview-links">
                            <a href="{{ route('admin.towns.map') }}">Towns map</a>
                            ·
                            <a href="{{ route('admin.pois.map') }}">POIs map</a>
                        </span>
                    </div>
                </div>
            </div>

            <div class="admin-dashboard-overview-body">
                <div class="admin-dashboard-overview-filters-col">
                    <div class="admin-dashboard-overview-legend" aria-hidden="true">
                        <span><span class="admin-dashboard-overview-legend-dot" style="background:#4a7c59;"></span>Towns</span>
                        <span><span class="admin-dashboard-overview-legend-dot" style="background:#2e5f8a;"></span>POIs</span>
                        <span><span class="admin-dashboard-overview-legend-ring" style="border-color:#c49020;"></span>New (7 days)</span>
                    </div>
                    <p id="dash-map-counts" class="admin-dashboard-overview-counts" role="status"></p>

                    <div class="admin-dashboard-overview-filters">
                        <div class="towns-map-browse-filters admin-dashboard-overview-filter-row">
                            <label class="admin-dashboard-overview-check">
                                <input type="checkbox" id="dash-layer-pois" checked />
                                Show POIs
                            </label>
                            <label class="admin-dashboard-overview-check">
                                <input type="checkbox" id="dash-layer-towns" />
                                Show towns
                            </label>
                            <label class="admin-dashboard-overview-check">
                                <input type="checkbox" id="dash-new-only" />
                                New only (last 7 days)
                            </label>
                        </div>

                        <div class="towns-map-browse-filters admin-dashboard-overview-filter-row">
                            <label class="admin-dashboard-overview-field">
                                <span class="admin-dashboard-overview-field-label">State</span>
                                <select id="dash-state" class="towns-map-browse-select">
                                    <option value="">All NSW &amp; VIC</option>
                                    <option value="New South Wales">New South Wales</option>
                                    <option value="Victoria">Victoria</option>
                                </select>
                            </label>
                            <label class="admin-dashboard-overview-field">
                                <span class="admin-dashboard-overview-field-label">Photos</span>
                                <select id="dash-photos" class="towns-map-browse-select">
                                    <option value="any">Any</option>
                                    <option value="has">Has images</option>
                                    <option value="missing">Missing images</option>
                                </select>
                            </label>
                            <label class="admin-dashboard-overview-field">
                                <span class="admin-dashboard-overview-field-label">About text</span>
                                <select id="dash-about" class="towns-map-browse-select">
                                    <option value="any">Any</option>
                                    <option value="has">Has about</option>
                                    <option value="missing">Missing about</option>
                                </select>
                            </label>
                        </div>

                        <div class="admin-dashboard-overview-filter-heading">POIs</div>
                        <div class="towns-map-browse-filters admin-dashboard-overview-filter-row">
                            <label class="admin-dashboard-overview-field">
                                <span class="admin-dashboard-overview-field-label">Publication</span>
                                <select id="dash-poi-pub" class="towns-map-browse-select">
                                    <option value="any">Any</option>
                                    <option value="published">Published</option>
                                    <option value="draft">Draft</option>
                                    <option value="pending">Pending</option>
                                    <option value="unpublished">Not published</option>
                                </select>
                            </label>
                            <label class="admin-dashboard-overview-field">
                                <span class="admin-dashboard-overview-field-label">Verification</span>
                                <select id="dash-poi-ver" class="towns-map-browse-select">
                                    <option value="">Any</option>
                                    @foreach ($poiVerificationOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="admin-dashboard-overview-field">
                                <span class="admin-dashboard-overview-field-label">Narration audio</span>
                                <select id="dash-poi-narr" class="towns-map-browse-select">
                                    <option value="any">Any</option>
                                    <option value="has">Has narration</option>
                                    <option value="missing">Missing narration</option>
                                </select>
                            </label>
                        </div>
                        <label class="admin-dashboard-overview-field admin-dashboard-overview-field--block">
                            <span class="admin-dashboard-overview-field-label">POI categories (hold Ctrl/⌘ to select several)</span>
                            <select id="dash-categories" class="towns-map-browse-select admin-dashboard-overview-categories" multiple size="4">
                                @foreach ($categoryOptions as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </label>

                        <div class="admin-dashboard-overview-filter-heading">Towns</div>
                        <div class="towns-map-browse-filters admin-dashboard-overview-filter-row">
                            <label class="admin-dashboard-overview-field">
                                <span class="admin-dashboard-overview-field-label">Publication</span>
                                <select id="dash-town-pub" class="towns-map-browse-select">
                                    <option value="any">Any</option>
                                    <option value="published">Published</option>
                                    <option value="draft">Draft</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </label>
                            <label class="admin-dashboard-overview-field">
                                <span class="admin-dashboard-overview-field-label">Verification</span>
                                <select id="dash-town-ver" class="towns-map-browse-select">
                                    <option value="">Any</option>
                                    @foreach ($townVerificationOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    </div>

                    <div class="admin-dashboard-overview-actions">
                        <button type="button" id="dash-map-reset-filters" class="btn btn-neutral btn-sm">Reset filters</button>
                    </div>
                </div>

                <div class="admin-dashboard-overview-map-col">
                    <p id="dashboard-map-load-error" class="towns-map-browse-error" hidden></p>

                    @if ($overviewMap['enabled'])
                        @include('admin.towns._town_map_assets')
                        <div id="dashboard-admin-overview-map" class="admin-dashboard-overview-map-el" role="region" aria-label="NSW and Victoria content overview map"></div>
                    @else
                        <p class="towns-map-browse-disabled admin-dashboard-overview-map-disabled">
                            Map preview is off. Add <code>MAPTILER_API_KEY</code> to <code>.env</code>, run
                            <code>php artisan config:clear</code>, then reload. Keys stay on the server.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Next steps</div>
                    <div class="card-subtitle">Start adding content</div>
                </div>
            </div>
            <div style="padding:14px 16px;">
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a class="btn btn-primary btn-sm" href="{{ route('admin.towns.create') }}">+ Add town</a>
                    <a class="btn btn-neutral btn-sm" href="{{ route('admin.towns.index') }}">View towns</a>
                    <a class="btn btn-primary btn-sm" href="{{ route('admin.pois.create') }}">+ Add POI</a>
                    <a class="btn btn-neutral btn-sm" href="{{ route('admin.pois.index') }}">View POIs</a>
                </div>
            </div>
        </div>
    </div>

    @if ($overviewMap['enabled'])
        <script>
            window.__UT_ADMIN_DASHBOARD_OVERVIEW_MAP = @json($overviewMap);
        </script>
        <script defer src="{{ asset('js/admin-dashboard-overview-map.js') }}?v=3"></script>
    @endif

    <script>
        (function () {
            function el(id) {
                return document.getElementById(id);
            }
            function setChecked(id, on) {
                var n = el(id);
                if (n) {
                    n.checked = on;
                }
            }
            function setValue(id, val) {
                var n = el(id);
                if (n) {
                    n.value = val;
                }
            }
            function clearCategorySelection() {
                var cats = el('dash-categories');
                if (cats && cats.options) {
                    for (var i = 0; i < cats.options.length; i++) {
                        cats.options[i].selected = false;
                    }
                }
            }
            function resetFilterFields() {
                setChecked('dash-layer-pois', true);
                setChecked('dash-layer-towns', false);
                setChecked('dash-new-only', false);
                setValue('dash-state', '');
                setValue('dash-photos', 'any');
                setValue('dash-about', 'any');
                setValue('dash-poi-pub', 'any');
                setValue('dash-poi-ver', '');
                setValue('dash-poi-narr', 'any');
                setValue('dash-town-pub', 'any');
                setValue('dash-town-ver', '');
                clearCategorySelection();
            }
            function applyMapFiltersIfReady() {
                if (typeof window.__UT_DASHBOARD_MAP_APPLY_FILTERS === 'function') {
                    window.__UT_DASHBOARD_MAP_APPLY_FILTERS();
                }
            }
            function resetDashboardOverviewFilters() {
                resetFilterFields();
                applyMapFiltersIfReady();
            }
            function applyDashboardStatPreset(kind) {
                resetFilterFields();
                if (kind === 'towns') {
                    setChecked('dash-layer-pois', false);
                    setChecked('dash-layer-towns', true);
                } else if (kind === 'pois') {
                    setChecked('dash-layer-pois', true);
                    setChecked('dash-layer-towns', false);
                } else if (kind === 'pois-pending') {
                    setChecked('dash-layer-pois', true);
                    setChecked('dash-layer-towns', false);
                    setValue('dash-poi-pub', 'pending');
                } else if (kind === 'towns-pending') {
                    setChecked('dash-layer-pois', false);
                    setChecked('dash-layer-towns', true);
                    setValue('dash-town-pub', 'pending');
                } else if (kind === 'pois-published') {
                    setChecked('dash-layer-pois', true);
                    setChecked('dash-layer-towns', false);
                    setValue('dash-poi-pub', 'published');
                } else if (kind === 'towns-published') {
                    setChecked('dash-layer-pois', false);
                    setChecked('dash-layer-towns', true);
                    setValue('dash-town-pub', 'published');
                }
                applyMapFiltersIfReady();
                var mapBlock = document.getElementById('admin-dashboard-overview');
                if (mapBlock) {
                    mapBlock.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
            function bind() {
                var btn = document.getElementById('dash-map-reset-filters');
                if (!btn || btn.getAttribute('data-dash-reset-bound') === '1') {
                    return;
                }
                btn.setAttribute('data-dash-reset-bound', '1');
                btn.addEventListener('click', resetDashboardOverviewFilters);
            }
            function bindStatRow() {
                var row = document.querySelector('[data-admin-stats-row]');
                if (!row || row.getAttribute('data-dash-stats-bound') === '1') {
                    return;
                }
                row.setAttribute('data-dash-stats-bound', '1');
                row.addEventListener('click', function (e) {
                    var t = e.target && e.target.closest('[data-dash-stat]');
                    if (!t) {
                        return;
                    }
                    var k = t.getAttribute('data-dash-stat');
                    if (k) {
                        applyDashboardStatPreset(k);
                    }
                });
            }
            function bootDashFilters() {
                bind();
                bindStatRow();
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bootDashFilters);
            } else {
                bootDashFilters();
            }
        })();
    </script>
@endsection
