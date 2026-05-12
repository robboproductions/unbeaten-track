@php
    /** @var \App\Models\Town|null $town */
    /** @var list<string> $stateOptions */
    /** @var array<string, list<string>> $regionsByState */
    /** @var string $selectedState */
    /** @var string $selectedRegion */
    /** @var array{enabled: bool, styleUrl: string|null, proxyUrl: string|null, initialLat: float|null, initialLng: float|null, defaultZoom: int, revert?: array<string, string>} $adminMap */
    $town = $town ?? null;
    $townAboutAi = $townAboutAi ?? [
        'enabled' => false,
        'url' => null,
        'hint' => null,
    ];
    $adminMap = $adminMap ?? [
        'enabled' => false,
        'styleUrl' => null,
        'proxyUrl' => null,
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

<div class="town-form-stack">
    <section class="town-form-section" aria-labelledby="town-section-location">
        <h2 id="town-section-location" class="town-form-section-title">
            <span class="town-form-section-num">1.</span>
            Location details
        </h2>
        <div class="town-form-fields">
            <div class="town-form-field">
                <label class="town-form-label" for="town_name">Town name</label>
                <input
                    id="town_name"
                    name="name"
                    type="text"
                    value="{{ old('name', $town?->name) }}"
                    required
                    class="town-form-control town-form-control--emphasis"
                    autocomplete="off"
                />
                @error('name')<p class="town-form-error">{{ $message }}</p>@enderror
            </div>

            @include('admin.towns._state_region_fields', [
                'stateOptions' => $stateOptions,
                'regionsByState' => $regionsByState,
                'selectedState' => $selectedState,
                'selectedRegion' => $selectedRegion,
            ])

            <div class="town-form-field-row town-form-field-row--latlng-actions">
                <div class="town-form-field">
                    <label class="town-form-label" for="town_latitude">Latitude</label>
                    <input
                        id="town_latitude"
                        name="latitude"
                        type="text"
                        inputmode="decimal"
                        class="town-form-control"
                        value="{{ old('latitude', $town?->latitude) }}"
                    />
                    @error('latitude')<p class="town-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="town-form-field">
                    <label class="town-form-label" for="town_longitude">Longitude</label>
                    <input
                        id="town_longitude"
                        name="longitude"
                        type="text"
                        inputmode="decimal"
                        class="town-form-control"
                        value="{{ old('longitude', $town?->longitude) }}"
                    />
                    @error('longitude')<p class="town-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="town-form-field town-form-field--map-action">
                    <span class="town-form-label town-form-label--spacer" aria-hidden="true">&nbsp;</span>
                    <button
                        type="button"
                        id="town_map_show_btn"
                        class="btn btn-neutral btn-sm"
                        @disabled(! $adminMap['enabled'])
                        @if (! $adminMap['enabled']) title="Add MAPTILER_API_KEY to .env and refresh to enable the map." @endif
                    >Show on map</button>
                    <button type="button" id="town_map_revert_btn" class="town-form-map-revert">Revert</button>
                </div>
            </div>
            <p class="town-form-hint">“Show on map” updates the preview only until you save the town.</p>
        </div>
    </section>

    <section class="town-form-section" aria-labelledby="town-section-editorial">
        <h2 id="town-section-editorial" class="town-form-section-title">
            <span class="town-form-section-num">2.</span>
            Editorial content
        </h2>
        <div class="town-form-fields">
            <div class="town-form-field town-form-field--population">
                <label class="town-form-label" for="town_population">Approx. population</label>
                <input
                    id="town_population"
                    name="population_approx"
                    type="number"
                    min="0"
                    class="town-form-control"
                    value="{{ old('population_approx', $town?->population_approx) }}"
                />
                @error('population_approx')<p class="town-form-error">{{ $message }}</p>@enderror
            </div>

            <div class="town-form-field">
                <label class="town-form-label" for="town_editorial_hook">Editorial hook</label>
                <textarea
                    id="town_editorial_hook"
                    name="editorial_hook"
                    rows="4"
                    class="town-form-control town-form-control--textarea"
                >{{ old('editorial_hook', $town?->editorial_hook) }}</textarea>
                @error('editorial_hook')<p class="town-form-error">{{ $message }}</p>@enderror
            </div>

            <div class="town-form-field town-form-field--about">
                <div class="town-about-editor-head">
                    <label class="town-form-label" for="town_about_html">About the town</label>
                    <div class="town-about-ai-actions">
                        <button
                            type="button"
                            class="btn btn-neutral btn-sm"
                            id="town_about_ai_btn"
                            @disabled(! $townAboutAi['enabled'])
                            @if (! $townAboutAi['enabled'] && ($townAboutAi['hint'] ?? null))
                                title="{{ e($townAboutAi['hint']) }}"
                            @endif
                        >Draft with AI</button>
                    </div>
                </div>
                @if (! $townAboutAi['enabled'] && ($townAboutAi['hint'] ?? null))
                    <p class="town-form-hint town-about-ai-hint">{{ $townAboutAi['hint'] }}</p>
                @endif
                <p id="town_about_ai_message" class="town-about-ai-message" hidden></p>
                <textarea
                    id="town_about_html"
                    name="about_html"
                    rows="12"
                    class="town-form-control town-form-control--textarea town-about-html-textarea"
                >{{ old('about_html', $town?->about_html) }}</textarea>
                @error('about_html')<p class="town-form-error">{{ $message }}</p>@enderror
                <p class="town-form-hint">Rich text for public-facing copy. AI drafts are suggestions—always review for accuracy.</p>
            </div>

            <div class="town-form-field">
                <label class="town-form-label" for="town_spreadsheet_notes">Import / internal notes</label>
                <textarea
                    id="town_spreadsheet_notes"
                    name="spreadsheet_notes"
                    rows="3"
                    class="town-form-control town-form-control--textarea"
                >{{ old('spreadsheet_notes', $town?->spreadsheet_notes) }}</textarea>
                <p class="town-form-hint">Starter data or spreadsheet context (not shown on the public site).</p>
                @error('spreadsheet_notes')<p class="town-form-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <section class="town-form-section" aria-labelledby="town-section-services">
        <h2 id="town-section-services" class="town-form-section-title">
            <span class="town-form-section-num">3.</span>
            Service flags and categories
        </h2>
        <div class="town-form-fields">
            <fieldset class="town-form-fieldset">
                <legend class="town-form-label">Services present in town</legend>
                <div class="town-form-flags">
                    @foreach (['has_pub' => 'Pub', 'has_cafe' => 'Cafe', 'has_shop' => 'Shop', 'has_fuel' => 'Fuel', 'has_caravan_park' => 'Caravan park'] as $field => $label)
                        <label class="town-form-flag">
                            <input type="hidden" name="{{ $field }}" value="0" />
                            <input
                                type="checkbox"
                                name="{{ $field }}"
                                value="1"
                                @checked(old($field, ($town && $town->{$field}) ? '1' : '0') === '1')
                            />
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="town-form-field">
                <label class="town-form-label" for="town_likely_poi">Likely POI categories</label>
                <input
                    id="town_likely_poi"
                    name="likely_poi_categories"
                    type="text"
                    class="town-form-control"
                    value="{{ old('likely_poi_categories', $town?->likely_poi_categories) }}"
                />
                @error('likely_poi_categories')<p class="town-form-error">{{ $message }}</p>@enderror
            </div>

            <div class="town-form-field">
                <label class="town-form-label" for="town_suggested_corridor">Suggested corridor</label>
                <input
                    id="town_suggested_corridor"
                    name="suggested_corridor"
                    type="text"
                    class="town-form-control"
                    value="{{ old('suggested_corridor', $town?->suggested_corridor) }}"
                />
                @error('suggested_corridor')<p class="town-form-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>
</div>

@push('scripts')
    @once
        <script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
    @endonce
    <script src="{{ asset('js/admin-town-about-editor.js') }}?v=1"></script>
    <script>
        window.__UT_TOWN_ABOUT_EDITOR = @json(['ai' => $townAboutAi]);
    </script>
@endpush
