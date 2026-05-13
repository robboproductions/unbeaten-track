<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PoiVerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Poi;
use App\Models\Town;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const OVERVIEW_STATES = ['New South Wales', 'Victoria'];

    public function index(): View
    {
        $newSince = Carbon::now()->subWeek();

        $towns = Town::query()
            ->whereIn('state', self::OVERVIEW_STATES)
            ->withCount('photos')
            ->with('primaryPhoto')
            ->orderBy('name')
            ->get();

        $pois = Poi::query()
            ->whereIn('state', self::OVERVIEW_STATES)
            ->with(['town', 'primaryPhoto'])
            ->withCount('photos')
            ->orderBy('name')
            ->get();

        $townFeatures = $towns
            ->filter(fn (Town $t) => $this->townHasValidCoordinates($t))
            ->map(fn (Town $town) => $this->townToMapFeature($town, $newSince))
            ->values()
            ->all();

        $poiFeatures = $pois
            ->filter(fn (Poi $p) => $p->hasValidCoordinates())
            ->map(fn (Poi $poi) => $this->poiToMapFeature($poi, $newSince))
            ->values()
            ->all();

        $enabled = filled(config('maptiler.api_key'));

        $overviewMap = [
            'enabled' => $enabled,
            'styleUrl' => $enabled ? route('admin.maps.style') : null,
            'proxyUrl' => $enabled ? route('admin.maps.proxy') : null,
            'townsGeojson' => [
                'type' => 'FeatureCollection',
                'features' => $townFeatures,
            ],
            'poisGeojson' => [
                'type' => 'FeatureCollection',
                'features' => $poiFeatures,
            ],
            'townCount' => count($townFeatures),
            'poiCount' => count($poiFeatures),
            'centerFallback' => [145.4, -34.0],
            'zoomFallback' => 5.7,
            'colors' => [
                'townFill' => '#4a7c59',
                'poiFill' => '#2e5f8a',
                'newStroke' => '#c49020',
            ],
        ];

        return view('admin.dashboard', [
            'stats' => [
                'towns' => Town::count(),
                'pois' => Poi::count(),
                'pendingPois' => Poi::query()->where('status', 'pending')->count(),
                'publishedPois' => Poi::query()->where('status', 'published')->count(),
                'pendingTowns' => Town::query()->where('status', 'pending')->count(),
                'publishedTowns' => Town::query()->where('status', 'published')->count(),
            ],
            'overviewMap' => $overviewMap,
            'categoryOptions' => config('poi_taxonomy.categories', []),
            'poiVerificationOptions' => PoiVerificationStatus::options(),
            'townVerificationOptions' => config('town_verification.statuses', []),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function townToMapFeature(Town $town, Carbon $newSince): array
    {
        $photosCount = (int) ($town->photos_count ?? 0);
        $isNew = $town->created_at !== null && $town->created_at->greaterThanOrEqualTo($newSince);

        $properties = [
            'id' => $town->id,
            'name' => $town->name,
            'state' => $town->state,
            'region' => (string) ($town->region ?? ''),
            'status' => $town->status,
            'verificationStatus' => (string) ($town->verification_status ?? 'unverified'),
            'photosCount' => $photosCount,
            'hasPhotos' => $photosCount > 0 ? 1 : 0,
            'hasAbout' => $this->hasMeaningfulHtml($town->about_html) ? 1 : 0,
            'isNew' => $isNew ? 1 : 0,
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
    }

    /**
     * @return array<string, mixed>
     */
    private function poiToMapFeature(Poi $poi, Carbon $newSince): array
    {
        $photosCount = (int) ($poi->photos_count ?? 0);
        $isNew = $poi->created_at !== null && $poi->created_at->greaterThanOrEqualTo($newSince);
        $cats = $poi->categoryList();

        $properties = [
            'id' => $poi->id,
            'name' => $poi->name,
            'state' => $poi->state,
            'status' => $poi->status,
            'verificationStatus' => $poi->verification_status instanceof PoiVerificationStatus
                ? $poi->verification_status->value
                : (string) ($poi->verification_status ?? 'not_verified'),
            'townName' => $poi->town?->name ?? '',
            'categoriesLabel' => implode(', ', $cats),
            'categorySlugs' => implode(',', $cats),
            'photosCount' => $photosCount,
            'hasPhotos' => $photosCount > 0 ? 1 : 0,
            'hasAbout' => $this->hasMeaningfulHtml($poi->about_html) ? 1 : 0,
            'hasNarration' => filled($poi->narration_audio_path) ? 1 : 0,
            'isNew' => $isNew ? 1 : 0,
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
    }

    private function hasMeaningfulHtml(?string $html): bool
    {
        if ($html === null || $html === '') {
            return false;
        }

        return trim(strip_tags($html)) !== '';
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
}
