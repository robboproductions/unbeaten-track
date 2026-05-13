<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Town;
use App\Services\TownAboutAiDraftService;
use App\Services\TownAboutHtmlSanitizer;
use App\Services\TownNarrationService;
use App\Services\TownPhotoService;
use App\Support\AustraliaGeography;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TownController extends Controller
{
    public function index(Request $request)
    {
        $towns = $this->townIndexFilteredQuery($request)->paginate(12)->withQueryString();

        return view('admin.towns.index', [
            'towns' => $towns,
            'stateOptions' => AustraliaGeography::states(),
        ]);
    }

    public function map(Request $request)
    {
        $all = $this->townIndexFilteredQuery($request)->with('primaryPhoto')->get();
        $withCoords = $all->filter(fn (Town $t) => $this->townHasValidCoordinates($t));

        $features = $withCoords->map(function (Town $town) {
            $properties = [
                'id' => $town->id,
                'name' => $town->name,
                'state' => $town->state,
                'region' => (string) ($town->region ?? ''),
                'status' => $town->status,
                'photosCount' => (int) ($town->photos_count ?? 0),
                'editUrl' => route('admin.towns.edit', $town),
            ];
            if ($town->primaryPhoto !== null) {
                $properties['primaryPhotoUrl'] = $town->primaryPhoto->publicUrl();
            }

            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $town->longitude, (float) $town->latitude],
                ],
                'properties' => $properties,
            ];
        })->values()->all();

        $geojson = [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];

        $mapBrowse = $this->adminTownsMapBrowseConfig($geojson, $all->count(), $withCoords->count());

        return view('admin.towns.map', [
            'mapBrowse' => $mapBrowse,
            'stateOptions' => AustraliaGeography::states(),
            'totalTownRows' => $all->count(),
            'mappedTownCount' => $withCoords->count(),
        ]);
    }

    public function create()
    {
        return view('admin.towns.create', [
            'stateOptions' => AustraliaGeography::states(),
            'regionsByState' => AustraliaGeography::regionsByState(),
            'adminMap' => $this->adminTownMapConfig(null),
            'townAboutAi' => $this->townAboutAiConfig(null),
            'townNarrationAi' => $this->townNarrationAiConfig(null),
        ]);
    }

    public function store(Request $request, TownPhotoService $photoService)
    {
        $this->validatePhotos($request);

        $town = Town::create($this->validatedTown($request));
        $photoService->attachUploads($town, $request->file('photos', []));

        return redirect()->route('admin.towns.edit', $town)->with('status', 'Town created.');
    }

    public function edit(Town $town)
    {
        $town->load([
            'photos' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
            'publishedByUser:id,name',
            'verifiedByUser:id,name',
            'narrationGeneratedByUser:id,name',
        ]);

        $narration = app(TownNarrationService::class);

        return view('admin.towns.edit', [
            'town' => $town,
            'stateOptions' => AustraliaGeography::states(),
            'regionsByState' => AustraliaGeography::regionsByState(),
            'adminMap' => $this->adminTownMapConfig($town),
            'townAboutAi' => $this->townAboutAiConfig($town),
            'townNarrationAi' => $this->townNarrationAiConfig($town),
            'townNarration' => [
                'enabled' => (bool) config('poi_narration.enabled'),
                'configured' => $narration->isConfigured(),
                'isStale' => $narration->isStale($town),
                'generateUrl' => route('admin.towns.narration.generate', $town),
                'destroyUrl' => route('admin.towns.narration.destroy', $town),
            ],
        ]);
    }

    public function update(Request $request, Town $town, TownPhotoService $photoService)
    {
        $this->validatePhotos($request);

        $town->update($this->validatedTown($request, $town));
        $photoService->attachUploads($town, $request->file('photos', []));

        return redirect()->route('admin.towns.edit', $town)->with('status', 'Changes saved.');
    }

    public function destroy(Town $town)
    {
        $town->delete();

        return redirect()->route('admin.towns.index')->with('status', 'Town deleted.');
    }

    /**
     * @param  array<string, mixed>  $geojson
     * @return array<string, mixed>
     */
    private function adminTownsMapBrowseConfig(array $geojson, int $totalMatching, int $withCoordinates): array
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
    private function townAboutAiConfig(?Town $town): array
    {
        $configured = app(TownAboutAiDraftService::class)->isConfigured();

        if ($town === null) {
            return [
                'enabled' => false,
                'url' => null,
                'hint' => 'Save the town first, then use “Draft with Claude” on the edit screen.',
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
            'url' => route('admin.towns.ai-about-draft', $town),
            'hint' => null,
        ];
    }

    /**
     * @return array{enabled: bool, url: string|null, hint: string|null}
     */
    private function townNarrationAiConfig(?Town $town): array
    {
        $configured = app(TownAboutAiDraftService::class)->isConfigured();

        if ($town === null) {
            return [
                'enabled' => false,
                'url' => null,
                'hint' => 'Save the town first, then use "Draft Script with Claude" on the edit screen.',
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
            'url' => route('admin.towns.ai-narration-script-draft', $town),
            'hint' => null,
        ];
    }

    private function townIndexFilteredQuery(Request $request): Builder
    {
        $query = Town::query()->withCount('photos')->orderBy('name');

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->string('q').'%');
        }

        if ($request->filled('state')) {
            $query->where('state', $request->string('state'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return $query;
    }

    private function townHasValidCoordinates(Town $town): bool
    {
        if ($town->latitude === null || $town->longitude === null) {
            return false;
        }

        $lat = (float) $town->latitude;
        $lng = (float) $town->longitude;

        return $lat >= -90.0 && $lat <= 90.0 && $lng >= -180.0 && $lng <= 180.0;
    }

    /**
     * Map preview on town create/edit (MapLibre + MapTiler via server-side key handling).
     *
     * @return array{
     *     enabled: bool,
     *     styleUrl: string|null,
     *     proxyUrl: string|null,
     *     geocodeUrl: string|null,
     *     initialLat: float|null,
     *     initialLng: float|null,
     *     defaultZoom: int,
     *     revert: array{latitude: string, longitude: string, population_approx: string}
     * }
     */
    private function adminTownMapConfig(?Town $town): array
    {
        $enabled = filled(config('maptiler.api_key'));

        $latRaw = $town !== null
            ? old('latitude', $town->latitude)
            : old('latitude');
        $lngRaw = $town !== null
            ? old('longitude', $town->longitude)
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
                'latitude' => $this->adminTownFieldOriginalDisplay('latitude', $town),
                'longitude' => $this->adminTownFieldOriginalDisplay('longitude', $town),
                'population_approx' => $this->adminTownFieldOriginalDisplay('population_approx', $town),
                'status' => $town !== null
                    ? (string) old('status', $town->status ?? 'draft')
                    : (string) old('status', 'draft'),
                'verification_status' => $town !== null
                    ? (string) old('verification_status', $town->verification_status ?? 'unverified')
                    : (string) old('verification_status', 'unverified'),
            ],
        ];
    }

    /**
     * Value shown when the town form first rendered (respects old() after validation errors).
     */
    private function adminTownFieldOriginalDisplay(string $attribute, ?Town $town): string
    {
        $value = $town !== null
            ? old($attribute, $town->{$attribute})
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
     * @return array<string, mixed>
     */
    private function validatedTown(Request $request, ?Town $town = null): array
    {
        $stateNorm = AustraliaGeography::normalizeStateInput((string) $request->input('state'));
        $maxNarration = (int) config('poi_narration.limits.max_script_characters', 5000);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', Rule::in(AustraliaGeography::states())],
            'region' => ['nullable', 'string', 'max:255', Rule::in($this->allowedRegionsForValidation($stateNorm, (string) $request->input('region', ''), $town))],
            'status' => ['required', 'in:published,draft,pending'],
            'verification_status' => ['required', 'string', Rule::in(array_keys(config('town_verification.statuses')))],
            'population_approx' => ['nullable', 'integer', 'min:0', 'max:50000000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'has_pub' => ['required', 'in:0,1'],
            'has_cafe' => ['required', 'in:0,1'],
            'has_shop' => ['required', 'in:0,1'],
            'has_fuel' => ['required', 'in:0,1'],
            'has_caravan_park' => ['required', 'in:0,1'],
            'editorial_hook' => ['nullable', 'string', 'max:20000'],
            'about_html' => ['nullable', 'string', 'max:500000'],
            'narration_script' => ['nullable', 'string', 'max:'.$maxNarration],
            'likely_poi_categories' => ['nullable', 'string', 'max:255'],
            'suggested_corridor' => ['nullable', 'string', 'max:255'],
            'spreadsheet_notes' => ['nullable', 'string', 'max:20000'],
        ]);

        $validated['state'] = AustraliaGeography::normalizeStateInput($validated['state']);
        if (array_key_exists('region', $validated) && $validated['region'] === '') {
            $validated['region'] = null;
        }

        if (array_key_exists('about_html', $validated)) {
            $sanitized = app(TownAboutHtmlSanitizer::class)->sanitize($validated['about_html'] ?? '');
            $validated['about_html'] = $sanitized === '' ? null : $sanitized;
        }

        if (array_key_exists('narration_script', $validated) && $validated['narration_script'] !== null) {
            $trimmed = trim((string) $validated['narration_script']);
            $validated['narration_script'] = $trimmed === '' ? null : $trimmed;
        }

        return $validated;
    }

    /**
     * @return list<string>
     */
    private function allowedRegionsForValidation(string $stateNormalized, string $submittedRegion, ?Town $town): array
    {
        $list = AustraliaGeography::regionsForState($stateNormalized);
        if ($submittedRegion !== '' && ! in_array($submittedRegion, $list, true)) {
            $list[] = $submittedRegion;
        }
        if ($town && $town->region && ! in_array($town->region, $list, true)) {
            $list[] = $town->region;
        }

        return array_values(array_unique(array_merge([''], $list)));
    }
}
