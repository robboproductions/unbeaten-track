@php
    /** @var \App\Models\Poi|null $poi */
    /** @var list<string> $stateOptions */
    /** @var list<string> $categoryOptions */
    /** @var \Illuminate\Support\Collection<int, \App\Models\Town> $towns */
    /** @var array{enabled: bool, styleUrl: string|null, proxyUrl: string|null, geocodeUrl: string|null, initialLat: float|null, initialLng: float|null, defaultZoom: int, revert?: array<string, string>} $adminPoiMap */
    $adminPoiMap = $adminPoiMap ?? [
        'enabled' => false,
        'styleUrl' => null,
        'proxyUrl' => null,
        'geocodeUrl' => null,
        'initialLat' => null,
        'initialLng' => null,
        'defaultZoom' => 4,
        'revert' => [],
    ];
    $poi = $poi ?? null;
    $poiAboutAi = $poiAboutAi ?? [
        'enabled' => false,
        'url' => null,
        'hint' => null,
    ];
    $poiNarration = $poiNarration ?? [
        'enabled' => false,
        'configured' => false,
        'isStale' => false,
        'generateUrl' => null,
        'destroyUrl' => null,
    ];
    $poiNarrationAi = $poiNarrationAi ?? [
        'enabled' => false,
        'url' => null,
        'hint' => null,
    ];
@endphp

<div class="town-form-stack">
    <section class="town-form-section" aria-labelledby="poi-section-core">
        <h2 id="poi-section-core" class="town-form-section-title">
            <span class="town-form-section-num">1.</span>
            POI details
        </h2>
        <div class="town-form-fields">
            <div class="town-form-field">
                <label class="town-form-label" for="poi_name">Name</label>
                <input
                    id="poi_name"
                    name="name"
                    type="text"
                    value="{{ old('name', $poi?->name) }}"
                    required
                    class="town-form-control town-form-control--emphasis"
                    autocomplete="off"
                />
                @error('name')<p class="town-form-error">{{ $message }}</p>@enderror
            </div>

            <div class="town-form-field">
                <label class="town-form-label" for="poi_categories">Categories</label>
                @php
                    $selCats = old('categories', $poi?->categoryList() ?? []);
                    $selCats = is_array($selCats) ? $selCats : [$selCats];
                @endphp
                <select
                    id="poi_categories"
                    name="categories[]"
                    multiple
                    required
                    size="5"
                    class="town-form-control town-form-control--select"
                    style="min-height:120px;max-width:100%;"
                >
                    @foreach ($categoryOptions as $cat)
                        <option value="{{ $cat }}" @selected(in_array($cat, $selCats, true))>{{ $cat }}</option>
                    @endforeach
                </select>
                <p class="town-form-hint">Hold Ctrl (Windows) or ⌘ (Mac) to select more than one.</p>
                @error('categories')<p class="town-form-error">{{ $message }}</p>@enderror
                @error('categories.*')<p class="town-form-error">{{ $message }}</p>@enderror
            </div>

            <div class="town-form-field-row">
                <div class="town-form-field">
                    <label class="town-form-label" for="poi_town_id">Town</label>
                    <select id="poi_town_id" name="town_id" required class="town-form-control town-form-control--select">
                        @foreach ($towns as $t)
                            <option value="{{ $t->id }}" @selected((string) old('town_id', $poi?->town_id) === (string) $t->id)>{{ $t->name }} ({{ $t->state }})</option>
                        @endforeach
                    </select>
                    @error('town_id')<p class="town-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="town-form-field">
                    <label class="town-form-label" for="poi_state">State / territory</label>
                    <select id="poi_state" name="state" required class="town-form-control town-form-control--select">
                        @foreach ($stateOptions as $st)
                            <option value="{{ $st }}" @selected(old('state', $poi?->state) === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                    @error('state')<p class="town-form-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="town-form-field-row town-form-field-row--latlng-actions">
                <div class="town-form-field">
                    <label class="town-form-label" for="poi_latitude">Latitude</label>
                    <input
                        id="poi_latitude"
                        name="latitude"
                        type="text"
                        inputmode="decimal"
                        class="town-form-control"
                        value="{{ old('latitude', $poi?->latitude) }}"
                    />
                    @error('latitude')<p class="town-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="town-form-field">
                    <label class="town-form-label" for="poi_longitude">Longitude</label>
                    <input
                        id="poi_longitude"
                        name="longitude"
                        type="text"
                        inputmode="decimal"
                        class="town-form-control"
                        value="{{ old('longitude', $poi?->longitude) }}"
                    />
                    @error('longitude')<p class="town-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="town-form-field town-form-field--map-action">
                    <span class="town-form-label town-form-label--spacer" aria-hidden="true">&nbsp;</span>
                    <button
                        type="button"
                        id="poi_map_show_btn"
                        class="btn btn-neutral btn-sm"
                        @disabled(! $adminPoiMap['enabled'])
                        @if (! $adminPoiMap['enabled']) title="Add MAPTILER_API_KEY to .env and refresh to enable the map." @endif
                    >Show on map</button>
                    <button
                        type="button"
                        id="poi_map_geocode_btn"
                        class="btn btn-neutral btn-sm"
                        @disabled(! $adminPoiMap['enabled'])
                        @if (! $adminPoiMap['enabled']) title="Add MAPTILER_API_KEY to .env and refresh to enable lookup." @endif
                    >Lookup coordinates</button>
                    <button type="button" id="poi_map_revert_btn" class="town-form-map-revert">Revert</button>
                </div>
            </div>
            <p class="town-form-hint">Drag the map pin or click the map to set coordinates. “Lookup coordinates” uses MapTiler (Australia-biased) from the POI name, town, and state.</p>

            <div class="town-form-field">
                <label class="town-form-label" for="poi_short_description">Short description</label>
                <input
                    id="poi_short_description"
                    name="short_description"
                    type="text"
                    maxlength="180"
                    class="town-form-control"
                    value="{{ old('short_description', $poi?->short_description) }}"
                />
                @error('short_description')<p class="town-form-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <section class="town-form-section" aria-labelledby="poi-section-about">
        <h2 id="poi-section-about" class="town-form-section-title">
            <span class="town-form-section-num">2.</span>
            About this POI
        </h2>
        <div class="town-form-fields">
            <div class="town-form-field town-form-field--about">
                <div class="town-about-editor-head">
                    <label class="town-form-label" for="poi_about_html">Long description</label>
                    <div class="town-about-ai-actions">
                        <button
                            type="button"
                            class="btn btn-neutral btn-sm"
                            id="poi_about_ai_btn"
                            @disabled(! $poiAboutAi['enabled'])
                            @if (! $poiAboutAi['enabled'] && ($poiAboutAi['hint'] ?? null))
                                title="{{ e($poiAboutAi['hint']) }}"
                            @endif
                        >Draft with Claude</button>
                    </div>
                </div>
                @if (! $poiAboutAi['enabled'] && ($poiAboutAi['hint'] ?? null))
                    <p class="town-form-hint town-about-ai-hint">{{ $poiAboutAi['hint'] }}</p>
                @endif
                <p id="poi_about_ai_message" class="town-about-ai-message" hidden></p>
                <textarea
                    id="poi_about_html"
                    name="about_html"
                    rows="10"
                    class="town-form-control town-form-control--textarea town-about-html-textarea"
                >{{ old('about_html', $poi?->about_html) }}</textarea>
                @error('about_html')<p class="town-form-error">{{ $message }}</p>@enderror
                <p class="town-form-hint">Rich text for visitors. Claude drafts are suggestions, always review for accuracy.</p>
            </div>

            <div class="town-form-field">
                <div class="town-about-editor-head">
                    <label class="town-form-label" for="poi_narration_script">Narration script</label>
                    <div class="town-about-ai-actions">
                        <button
                            type="button"
                            class="btn btn-neutral btn-sm"
                            id="poi_narration_ai_btn"
                            @disabled(! $poiNarrationAi['enabled'])
                            @if (! $poiNarrationAi['enabled'] && ($poiNarrationAi['hint'] ?? null))
                                title="{{ e($poiNarrationAi['hint']) }}"
                            @endif
                        >Draft Script with Claude</button>
                    </div>
                </div>
                @if (! $poiNarrationAi['enabled'] && ($poiNarrationAi['hint'] ?? null))
                    <p class="town-form-hint town-about-ai-hint">{{ $poiNarrationAi['hint'] }}</p>
                @endif
                <p id="poi_narration_ai_message" class="town-about-ai-message" hidden></p>
                <textarea
                    id="poi_narration_script"
                    name="narration_script"
                    rows="6"
                    class="town-form-control town-form-control--textarea"
                    placeholder="Spoken intro for drivers when they reach this POI…"
                >{{ old('narration_script', $poi?->narration_script) }}</textarea>
                @error('narration_script')<p class="town-form-error">{{ $message }}</p>@enderror
                <p class="town-form-hint">
                    Write for the ear: aim for about 30 to 90 seconds spoken (roughly 80 to 250 words). This will be read by an AI voice, so use contractions and short sentences. You can use &quot;Draft Script with Claude&quot; for a tour-guide style first pass, then edit before saving and generating audio.
                </p>
                <p class="town-form-hint" style="margin-top:6px;">
                    <strong>Save the POI</strong> after you change the script, then use Generate so the latest text is sent to ElevenLabs.
                </p>

                @if ($poi)
                    @error('narration')<p class="town-form-error">{{ $message }}</p>@enderror
                    @error('narration_voice_id')<p class="town-form-error">{{ $message }}</p>@enderror

                    <div style="margin-top:12px;padding:12px 14px;border-radius:var(--radius-md);background:var(--color-river-stone);border:1px solid var(--color-border);">
                        <div style="font-size:12px;font-weight:600;color:var(--color-charcoal);margin-bottom:8px;">Audio narration</div>
                        <p class="town-form-hint" style="margin-bottom:10px;">
                            Choose <strong>Terry</strong> or <strong>Sarah</strong> when you generate; the POI stores which voice was used for the current file.
                        </p>

                        @if (! $poiNarration['enabled'])
                            <span style="display:inline-block;font-size:11px;font-weight:600;padding:3px 8px;border-radius:4px;background:var(--badge-draft-bg);color:var(--badge-draft-text);">Narration disabled</span>
                        @elseif (! $poiNarration['configured'])
                            <span style="display:inline-block;font-size:11px;font-weight:600;padding:3px 8px;border-radius:4px;background:var(--badge-draft-bg);color:var(--badge-draft-text);">Set ELEVENLABS_API_KEY to generate</span>
                        @elseif ($poi->has_narration && ! $poiNarration['isStale'])
                            <span style="display:inline-block;font-size:11px;font-weight:600;padding:3px 8px;border-radius:4px;background:var(--badge-in-progress-bg);color:var(--badge-in-progress-text);">Audio ready</span>
                        @elseif ($poi->has_narration && $poiNarration['isStale'])
                            <span style="display:inline-block;font-size:11px;font-weight:600;padding:3px 8px;border-radius:4px;background:var(--badge-saved-bg);color:var(--badge-saved-text);">Script changed: regenerate audio</span>
                        @elseif (filled($poi->narration_script))
                            <span style="display:inline-block;font-size:11px;font-weight:600;padding:3px 8px;border-radius:4px;background:var(--color-white);color:var(--color-mid-grey);border:1px solid var(--color-border);">No audio yet</span>
                        @else
                            <span style="display:inline-block;font-size:11px;font-weight:600;padding:3px 8px;border-radius:4px;background:var(--color-white);color:var(--color-mid-grey);border:1px solid var(--color-border);">Write a script first</span>
                        @endif

                        @if ($poi->has_narration && $poi->narration_audio_url)
                            <div style="margin-top:12px;">
                                <audio controls src="{{ $poi->narration_audio_url }}" style="width:100%;max-width:420px;vertical-align:middle;"></audio>
                            </div>
                            @if ($poi->narration_generated_at)
                                <p class="town-form-hint" style="margin-top:8px;">
                                    Generated {{ $poi->narration_generated_at->diffForHumans() }}
                                    @if ($poi->narration_audio_bytes)
                                        · {{ number_format(max(1, $poi->narration_audio_bytes) / 1024, 1) }} KB
                                    @endif
                                    @if ($poi->narrationGeneratedByUser)
                                        · by {{ $poi->narrationGeneratedByUser->name }}
                                    @endif
                                </p>
                            @endif
                        @endif

                        <div style="margin-top:12px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                            @if ($poiNarration['enabled'] && $poiNarration['configured'] && filled($poi->narration_script))
                                <button
                                    type="submit"
                                    class="btn btn-primary btn-sm"
                                    form="ut-poi-narration-generate-terry-{{ $poi->id }}"
                                >{{ $poi->has_narration ? 'Regenerate audio with Terry' : 'Generate audio with Terry' }}</button>
                                <button
                                    type="submit"
                                    class="btn btn-primary btn-sm"
                                    form="ut-poi-narration-generate-sarah-{{ $poi->id }}"
                                >{{ $poi->has_narration ? 'Regenerate audio with Sarah' : 'Generate audio with Sarah' }}</button>
                            @elseif ($poiNarration['enabled'] && $poiNarration['configured'])
                                <button type="button" class="btn btn-primary btn-sm" disabled title="Add a narration script first">Generate audio with Terry</button>
                                <button type="button" class="btn btn-primary btn-sm" disabled title="Add a narration script first">Generate audio with Sarah</button>
                            @endif

                            @if ($poi->has_narration && $poiNarration['destroyUrl'])
                                <button
                                    type="submit"
                                    class="btn btn-neutral btn-sm"
                                    form="ut-poi-narration-destroy-{{ $poi->id }}"
                                    onclick="return confirm('Remove this narration audio file?');"
                                >Delete audio</button>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="town-form-hint" style="margin-top:8px;">Save the POI first, then you can generate voice narration from the edit screen.</p>
                @endif
            </div>

            <div class="town-form-field">
                <label class="town-form-label" for="poi_spreadsheet_notes">Import / internal notes</label>
                <textarea
                    id="poi_spreadsheet_notes"
                    name="spreadsheet_notes"
                    rows="3"
                    class="town-form-control town-form-control--textarea"
                >{{ old('spreadsheet_notes', $poi?->spreadsheet_notes) }}</textarea>
                @error('spreadsheet_notes')<p class="town-form-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>
</div>

@push('scripts')
    @once
        <script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
    @endonce
    <script>
        window.__UT_POI_ABOUT_EDITOR = @json(['ai' => $poiAboutAi]);
        window.__UT_POI_NARRATION_DRAFT = @json(['ai' => $poiNarrationAi]);
    </script>
    <script src="{{ asset('js/admin-poi-about-editor.js') }}?v=1"></script>
    <script src="{{ asset('js/admin-poi-narration-draft.js') }}?v=1"></script>
@endpush
