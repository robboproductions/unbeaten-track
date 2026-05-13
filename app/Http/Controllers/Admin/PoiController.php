<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PoiVerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Poi;
use App\Models\Town;
use App\Services\PoiAboutAiDraftService;
use App\Services\PoiNarrationService;
use App\Services\PoiPhotoService;
use App\Services\TownAboutHtmlSanitizer;
use App\Support\AustraliaGeography;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PoiController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->poiIndexFilteredQuery($request)->with('town');

        $pois = $query->paginate(12)->withQueryString();

        return view('admin.pois.index', [
            'pois' => $pois,
            'stateOptions' => AustraliaGeography::states(),
            'categoryOptions' => config('poi_taxonomy.categories', []),
        ]);
    }

    public function map(Request $request)
    {
        $all = $this->poiIndexFilteredQuery($request)->with('primaryPhoto')->get();
        $withCoords = $all->filter(fn (Poi $p) => $p->hasValidCoordinates());

        $features = $withCoords->map(function (Poi $poi) {
            $properties = [
                'id' => $poi->id,
                'name' => $poi->name,
                'state' => $poi->state,
                'status' => $poi->status,
                'categories' => implode(', ', $poi->categoryList()),
                'townName' => $poi->town?->name ?? '',
                'editUrl' => route('admin.pois.edit', $poi),
            ];
            if ($poi->primaryPhoto !== null) {
                $properties['primaryPhotoUrl'] = $poi->primaryPhoto->publicUrl();
            }

            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $poi->longitude, (float) $poi->latitude],
                ],
                'properties' => $properties,
            ];
        })->values()->all();

        $geojson = [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];

        $mapBrowse = $this->adminPoisMapBrowseConfig($geojson, $all->count(), $withCoords->count());

        return view('admin.pois.map', [
            'mapBrowse' => $mapBrowse,
            'stateOptions' => AustraliaGeography::states(),
            'categoryOptions' => config('poi_taxonomy.categories', []),
            'totalPoiRows' => $all->count(),
            'mappedPoiCount' => $withCoords->count(),
        ]);
    }

    public function create()
    {
        return view('admin.pois.create', [
            'towns' => Town::query()->orderBy('name')->get(),
            'stateOptions' => AustraliaGeography::states(),
            'categoryOptions' => config('poi_taxonomy.categories', []),
            'adminPoiMap' => $this->adminPoiMapConfig(null),
            'poiAboutAi' => $this->poiAboutAiConfig(null),
            'poiNarrationAi' => $this->poiNarrationAiConfig(null),
        ]);
    }

    public function store(Request $request, PoiPhotoService $photoService)
    {
        $this->validatePhotos($request);

        $validated = $this->validatedPoi($request, null);
        $validated = $this->applyAboutHtmlSanitize($validated);

        $poi = Poi::create($validated);
        $photoService->attachUploads($poi, $request->file('photos', []));

        return redirect()->route('admin.pois.edit', $poi)->with('status', 'POI created.');
    }

    public function edit(Poi $poi)
    {
        $poi->load([
            'photos' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
            'town',
            'publishedByUser:id,name',
            'narrationGeneratedByUser:id,name',
        ]);

        $narration = app(PoiNarrationService::class);

        return view('admin.pois.edit', [
            'poi' => $poi,
            'towns' => Town::query()->orderBy('name')->get(),
            'stateOptions' => AustraliaGeography::states(),
            'categoryOptions' => $this->categoryOptionsForPoi($poi),
            'adminPoiMap' => $this->adminPoiMapConfig($poi),
            'poiAboutAi' => $this->poiAboutAiConfig($poi),
            'poiNarrationAi' => $this->poiNarrationAiConfig($poi),
            'poiNarration' => [
                'enabled' => (bool) config('poi_narration.enabled'),
                'configured' => $narration->isConfigured(),
                'isStale' => $narration->isStale($poi),
                'generateUrl' => route('admin.pois.narration.generate', $poi),
                'destroyUrl' => route('admin.pois.narration.destroy', $poi),
            ],
        ]);
    }

    public function update(Request $request, Poi $poi, PoiPhotoService $photoService)
    {
        $this->validatePhotos($request);

        $validated = $this->validatedPoi($request, $poi);
        $validated = $this->applyAboutHtmlSanitize($validated);

        $poi->update($validated);
        $photoService->attachUploads($poi, $request->file('photos', []));

        return redirect()->route('admin.pois.edit', $poi)->with('status', 'Changes saved.');
    }

    public function destroy(Poi $poi)
    {
        $poi->delete();

        return redirect()->route('admin.pois.index')->with('status', 'POI deleted.');
    }

    /**
     * @param  array<string, mixed>  $geojson
     * @return array<string, mixed>
     */
    private function adminPoisMapBrowseConfig(array $geojson, int $totalMatching, int $withCoordinates): array
    {
        $enabled = filled(config('maptiler.api_key'));

        return [
            'enabled' => $enabled,
            'styleUrl' => $enabled ? route('admin.maps.style') : null,
            'proxyUrl' => $enabled ? route('admin.maps.proxy') : null,
            'geojson' => $geojson,
            'nswCenter' => [150.75, -32.15],
            'nswZoom' => 6,
            'totalMatching' => $totalMatching,
            'withCoordinates' => $withCoordinates,
        ];
    }

    /**
     * @return array{enabled: bool, url: string|null, hint: string|null}
     */
    private function poiAboutAiConfig(?Poi $poi): array
    {
        $configured = app(PoiAboutAiDraftService::class)->isConfigured();

        if ($poi === null) {
            return [
                'enabled' => false,
                'url' => null,
                'hint' => 'Save the POI first, then use “Draft with Claude” on the edit screen.',
            ];
        }

        if (! $configured) {
            return [
                'enabled' => false,
                'url' => null,
                'hint' => 'Set ANTHROPIC_API_KEY in .env for Claude, then run php artisan config:clear (keys stay on the server only).',
            ];
        }

        return [
            'enabled' => true,
            'url' => route('admin.pois.ai-about-draft', $poi),
            'hint' => null,
        ];
    }

    /**
     * @return array{enabled: bool, url: string|null, hint: string|null}
     */
    private function poiNarrationAiConfig(?Poi $poi): array
    {
        $configured = app(PoiAboutAiDraftService::class)->isConfigured();

        if ($poi === null) {
            return [
                'enabled' => false,
                'url' => null,
                'hint' => 'Save the POI first, then use "Draft Script with Claude" on the edit screen.',
            ];
        }

        if (! $configured) {
            return [
                'enabled' => false,
                'url' => null,
                'hint' => 'Set ANTHROPIC_API_KEY or OPENAI_API_KEY in .env for Claude or OpenAI, then run php artisan config:clear (keys stay on the server only).',
            ];
        }

        return [
            'enabled' => true,
            'url' => route('admin.pois.ai-narration-script-draft', $poi),
            'hint' => null,
        ];
    }

    /**
     * @return array{
     *     enabled: bool,
     *     styleUrl: string|null,
     *     proxyUrl: string|null,
     *     geocodeUrl: string|null,
     *     initialLat: float|null,
     *     initialLng: float|null,
     *     defaultZoom: int,
     *     revert: array<string, string>
     * }
     */
    private function adminPoiMapConfig(?Poi $poi): array
    {
        $enabled = filled(config('maptiler.api_key'));

        $latRaw = $poi !== null
            ? old('latitude', $poi->latitude)
            : old('latitude');
        $lngRaw = $poi !== null
            ? old('longitude', $poi->longitude)
            : old('longitude');

        $lat = is_numeric($latRaw) ? (float) $latRaw : null;
        $lng = is_numeric($lngRaw) ? (float) $lngRaw : null;

        return [
            'enabled' => $enabled,
            'styleUrl' => $enabled ? route('admin.maps.style') : null,
            'proxyUrl' => $enabled ? route('admin.maps.proxy') : null,
            'geocodeUrl' => $enabled ? route('admin.maps.geocode') : null,
            'initialLat' => $lat,
            'initialLng' => $lng,
            'defaultZoom' => ($lat !== null && $lng !== null) ? 11 : 4,
            'revert' => [
                'latitude' => $this->adminPoiFieldOriginalDisplay('latitude', $poi),
                'longitude' => $this->adminPoiFieldOriginalDisplay('longitude', $poi),
                'status' => $poi !== null
                    ? (string) old('status', $poi->status ?? 'draft')
                    : (string) old('status', 'draft'),
            ],
        ];
    }

    private function adminPoiFieldOriginalDisplay(string $attribute, ?Poi $poi): string
    {
        $value = $poi !== null
            ? old($attribute, $poi->{$attribute})
            : old($attribute);

        if ($value === null || $value === '') {
            return '';
        }

        return (string) $value;
    }

    private function validatePhotos(Request $request): void
    {
        $request->validate([
            'photos' => ['sometimes', 'array', 'max:20'],
            'photos.*' => ['nullable', 'image', 'max:5120', 'mimes:jpeg,jpg,png,webp,gif'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function applyAboutHtmlSanitize(array $validated): array
    {
        if (! array_key_exists('about_html', $validated)) {
            return $validated;
        }

        $sanitized = app(TownAboutHtmlSanitizer::class)->sanitize($validated['about_html'] ?? '');
        $validated['about_html'] = $sanitized === '' ? null : $sanitized;

        return $validated;
    }

    private function poiIndexFilteredQuery(Request $request): Builder
    {
        $query = Poi::query()->withCount('photos')->orderBy('name');

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->string('q').'%');
        }

        if ($request->filled('state')) {
            $query->where('state', $request->string('state'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('category')) {
            $query->whereJsonContains('categories', $request->string('category'));
        }

        if ($request->filled('verification_status')) {
            $query->where('verification_status', $request->string('verification_status'));
        }

        if ($request->filled('narration')) {
            $n = (string) $request->string('narration');
            // Match Poi::$hasNarration (filled path): NOT NULL alone still includes legacy '' rows.
            if ($n === 'has') {
                $query->whereNotNull('narration_audio_path')
                    ->where('narration_audio_path', '!=', '');
            } elseif ($n === 'missing') {
                $query->where(function (Builder $q): void {
                    $q->whereNull('narration_audio_path')
                        ->orWhere('narration_audio_path', '=', '');
                });
            }
        }

        return $query;
    }

    /**
     * @return list<string>
     */
    private function categoryOptionsForPoi(?Poi $poi): array
    {
        $base = config('poi_taxonomy.categories', []);
        if (! $poi) {
            return $base;
        }

        foreach ($poi->categoryList() as $c) {
            if (! in_array($c, $base, true)) {
                $base[] = $c;
            }
        }

        return $base;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPoi(Request $request, ?Poi $poi): array
    {
        $allowedCats = $this->categoryOptionsForPoi($poi);
        $maxNarration = (int) config('poi_narration.limits.max_script_characters', 5000);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['required', 'string', Rule::in($allowedCats)],
            'town_id' => ['required', 'exists:towns,id'],
            'state' => ['required', 'string', Rule::in(AustraliaGeography::states())],
            'status' => ['required', 'in:published,draft,pending'],
            'verification_status' => ['required', Rule::enum(PoiVerificationStatus::class)],
            'verified_at' => ['nullable', 'date'],
            'short_description' => ['nullable', 'string', 'max:180'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'about_html' => ['nullable', 'string', 'max:500000'],
            'narration_script' => ['nullable', 'string', 'max:'.$maxNarration],
            'spreadsheet_notes' => ['nullable', 'string', 'max:20000'],
        ]);

        $validated['state'] = AustraliaGeography::normalizeStateInput($validated['state']);
        $validated['categories'] = array_values(array_unique($validated['categories']));

        if (($validated['verification_status'] ?? '') === PoiVerificationStatus::NotVerified->value) {
            $validated['verified_at'] = null;
        } elseif (empty($validated['verified_at'])) {
            $validated['verified_at'] = null;
        }

        if (array_key_exists('narration_script', $validated) && $validated['narration_script'] !== null) {
            $trimmed = trim((string) $validated['narration_script']);
            $validated['narration_script'] = $trimmed === '' ? null : $trimmed;
        }

        return $validated;
    }
}
