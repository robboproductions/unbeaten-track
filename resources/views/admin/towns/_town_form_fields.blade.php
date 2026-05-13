@php
    /** @var \App\Models\Town|null $town */
    /** @var list<string> $stateOptions */
    /** @var array<string, list<string>> $regionsByState */
    /** @var string $selectedState */
    /** @var string $selectedRegion */
    /** @var array{enabled: bool, styleUrl: string|null, proxyUrl: string|null, geocodeUrl: string|null, initialLat: float|null, initialLng: float|null, defaultZoom: int, revert?: array<string, string>} $adminMap */
    $town = $town ?? null;
    $townAboutAi = $townAboutAi ?? [
        'enabled' => false,
        'url' => null,
        'hint' => null,
    ];
    $townNarration = $townNarration ?? [
        'enabled' => false,
        'configured' => false,
        'isStale' => false,
        'generateUrl' => null,
        'destroyUrl' => null,
    ];
    $townNarrationAi = $townNarrationAi ?? [
        'enabled' => false,
        'url' => null,
        'hint' => null,
    ];
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
                    <button
                        type="button"
                        id="town_map_geocode_btn"
                        class="btn btn-neutral btn-sm"
                        @disabled(! $adminMap['enabled'])
                        @if (! $adminMap['enabled']) title="Add MAPTILER_API_KEY to .env and refresh to enable lookup." @endif
                    >Lookup coordinates</button>
                    <button type="button" id="town_map_revert_btn" class="town-form-map-revert">Revert</button>
                </div>
            </div>
            <p class="town-form-hint">Drag the map pin or click the map to set coordinates. “Lookup coordinates” uses MapTiler (Australia-biased) from the town name, region, and state.</p>
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
                        >Draft with Claude</button>
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
                <p class="town-form-hint">Rich text for public-facing copy. Claude drafts are suggestions—always review for accuracy.</p>
            </div>

            <div class="town-form-field">
                <div class="town-about-editor-head">
                    <label class="town-form-label" for="town_narration_script">Narration script</label>
                    <div class="town-about-ai-actions">
                        <button
                            type="button"
                            class="btn btn-neutral btn-sm"
                            id="town_narration_ai_btn"
                            @disabled(! $townNarrationAi['enabled'])
                            @if (! $townNarrationAi['enabled'] && ($townNarrationAi['hint'] ?? null))
                                title="{{ e($townNarrationAi['hint']) }}"
                            @endif
                        >Draft Script with Claude</button>
                    </div>
                </div>
                @if (! $townNarrationAi['enabled'] && ($townNarrationAi['hint'] ?? null))
                    <p class="town-form-hint town-about-ai-hint">{{ $townNarrationAi['hint'] }}</p>
                @endif
                <p id="town_narration_ai_message" class="town-about-ai-message" hidden></p>
                <textarea
                    id="town_narration_script"
                    name="narration_script"
                    rows="6"
                    class="town-form-control town-form-control--textarea"
                    placeholder="Spoken intro for drivers arriving in this town…"
                >{{ old('narration_script', $town?->narration_script) }}</textarea>
                @error('narration_script')<p class="town-form-error">{{ $message }}</p>@enderror
                <p class="town-form-hint">
                    Write for the ear: aim for about 30 to 90 seconds spoken (roughly 80 to 250 words). This will be read by an AI voice, so use contractions and short sentences. You can use &quot;Draft Script with Claude&quot; for a tour-guide style first pass, then edit before saving and generating audio.
                </p>
                <p class="town-form-hint" style="margin-top:6px;">
                    <strong>Save the town</strong> after you change the script, then use Generate so the latest text is sent to ElevenLabs.
                </p>

                @if ($town)
                    @error('narration')<p class="town-form-error">{{ $message }}</p>@enderror
                    @error('narration_voice_id')<p class="town-form-error">{{ $message }}</p>@enderror

                    <div style="margin-top:12px;padding:12px 14px;border-radius:var(--radius-md);background:var(--color-river-stone);border:1px solid var(--color-border);">
                        <div style="font-size:12px;font-weight:600;color:var(--color-charcoal);margin-bottom:8px;">Audio narration</div>
                        <p class="town-form-hint" style="margin-bottom:10px;">
                            Choose <strong>Terry</strong> or <strong>Sarah</strong> when you generate; the town stores which voice was used for the current file.
                        </p>

                        @if (! $townNarration['enabled'])
                            <span style="display:inline-block;font-size:11px;font-weight:600;padding:3px 8px;border-radius:4px;background:var(--badge-draft-bg);color:var(--badge-draft-text);">Narration disabled</span>
                        @elseif (! $townNarration['configured'])
                            <span style="display:inline-block;font-size:11px;font-weight:600;padding:3px 8px;border-radius:4px;background:var(--badge-draft-bg);color:var(--badge-draft-text);">Set ELEVENLABS_API_KEY to generate</span>
                        @elseif ($town->has_narration && ! $townNarration['isStale'])
                            <span style="display:inline-block;font-size:11px;font-weight:600;padding:3px 8px;border-radius:4px;background:var(--badge-in-progress-bg);color:var(--badge-in-progress-text);">Audio ready</span>
                        @elseif ($town->has_narration && $townNarration['isStale'])
                            <span style="display:inline-block;font-size:11px;font-weight:600;padding:3px 8px;border-radius:4px;background:var(--badge-saved-bg);color:var(--badge-saved-text);">Script changed: regenerate audio</span>
                        @elseif (filled($town->narration_script))
                            <span style="display:inline-block;font-size:11px;font-weight:600;padding:3px 8px;border-radius:4px;background:var(--color-white);color:var(--color-mid-grey);border:1px solid var(--color-border);">No audio yet</span>
                        @else
                            <span style="display:inline-block;font-size:11px;font-weight:600;padding:3px 8px;border-radius:4px;background:var(--color-white);color:var(--color-mid-grey);border:1px solid var(--color-border);">Write a script first</span>
                        @endif

                        @if ($town->has_narration && $town->narration_audio_url)
                            <div style="margin-top:12px;">
                                <audio controls src="{{ $town->narration_audio_url }}" style="width:100%;max-width:420px;vertical-align:middle;"></audio>
                            </div>
                            @if ($town->narration_generated_at)
                                <p class="town-form-hint" style="margin-top:8px;">
                                    Generated {{ $town->narration_generated_at->diffForHumans() }}
                                    @if ($town->narration_audio_bytes)
                                        · {{ number_format(max(1, $town->narration_audio_bytes) / 1024, 1) }} KB
                                    @endif
                                    @if ($town->narrationGeneratedByUser)
                                        · by {{ $town->narrationGeneratedByUser->name }}
                                    @endif
                                </p>
                            @endif
                        @endif

                        <div style="margin-top:12px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                            @if ($townNarration['enabled'] && $townNarration['configured'] && filled($town->narration_script))
                                <button
                                    type="submit"
                                    class="btn btn-primary btn-sm"
                                    form="ut-town-narration-generate-terry-{{ $town->id }}"
                                >{{ $town->has_narration ? 'Regenerate audio with Terry' : 'Generate audio with Terry' }}</button>
                                <button
                                    type="submit"
                                    class="btn btn-primary btn-sm"
                                    form="ut-town-narration-generate-sarah-{{ $town->id }}"
                                >{{ $town->has_narration ? 'Regenerate audio with Sarah' : 'Generate audio with Sarah' }}</button>
                            @elseif ($townNarration['enabled'] && $townNarration['configured'])
                                <button type="button" class="btn btn-primary btn-sm" disabled title="Add a narration script first">Generate audio with Terry</button>
                                <button type="button" class="btn btn-primary btn-sm" disabled title="Add a narration script first">Generate audio with Sarah</button>
                            @endif

                            @if ($town->has_narration && $townNarration['destroyUrl'])
                                <button
                                    type="submit"
                                    class="btn btn-neutral btn-sm"
                                    form="ut-town-narration-destroy-{{ $town->id }}"
                                    onclick="return confirm('Remove this narration audio file?');"
                                >Delete audio</button>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="town-form-hint" style="margin-top:8px;">Save the town first, then you can generate voice narration from the edit screen.</p>
                @endif
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
    <script>
        window.__UT_TOWN_ABOUT_EDITOR = @json(['ai' => $townAboutAi]);
        window.__UT_TOWN_NARRATION_DRAFT = @json(['ai' => $townNarrationAi]);
    </script>
    <script src="{{ asset('js/admin-town-about-editor.js') }}?v=2"></script>
    <script src="{{ asset('js/admin-town-narration-draft.js') }}?v=1"></script>
@endpush
