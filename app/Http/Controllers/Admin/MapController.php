<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MapController extends Controller
{
    /**
     * Forward geocode (MapTiler) — API key stays on the server.
     * Used by admin town/POI forms to refine coordinates.
     */
    public function geocode(Request $request): JsonResponse
    {
        $key = config('maptiler.api_key');
        if (! is_string($key) || $key === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Map geocoding is not configured (missing MAPTILER_API_KEY).',
            ], 503);
        }

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:512'],
        ]);

        $q = $validated['q'];
        $pathSegment = rawurlencode($q);
        $url = sprintf('https://api.maptiler.com/geocoding/%s.json', $pathSegment);

        $referer = trim((string) $request->headers->get('Referer', ''));
        if ($referer === '') {
            $referer = trim((string) $request->headers->get('Origin', ''));
        }
        if ($referer === '') {
            $referer = (string) config('maptiler.http_referer', config('app.url', 'http://localhost'));
        }

        $response = Http::timeout(25)
            ->withHeaders([
                'Referer' => $referer,
                'Accept' => 'application/json',
                'User-Agent' => 'UnbeatenTrackGeocode/1.0 (Laravel)',
            ])
            ->get($url, [
                'key' => $key,
                'limit' => 5,
                'country' => 'au',
                'autocomplete' => 'false',
                'bbox' => '113,-44,154,-10',
            ]);

        if (! $response->successful()) {
            return response()->json([
                'ok' => false,
                'message' => 'Geocoder request failed.',
            ], 502);
        }

        $data = $response->json();
        if (! is_array($data) || ($data['type'] ?? '') !== 'FeatureCollection') {
            return response()->json([
                'ok' => false,
                'message' => 'Unexpected geocoder response.',
            ], 502);
        }

        $features = $data['features'] ?? [];
        if (! is_array($features) || $features === []) {
            return response()->json([
                'ok' => false,
                'message' => 'No matching places found. Try a fuller place name or address.',
            ], 404);
        }

        $first = $features[0];
        if (! is_array($first)) {
            return response()->json([
                'ok' => false,
                'message' => 'Unexpected geocoder response.',
            ], 502);
        }

        $geometry = $first['geometry'] ?? null;
        if (! is_array($geometry) || ($geometry['type'] ?? '') !== 'Point') {
            return response()->json([
                'ok' => false,
                'message' => 'No point geometry in the top result.',
            ], 404);
        }

        $coords = $geometry['coordinates'] ?? null;
        if (! is_array($coords) || count($coords) < 2) {
            return response()->json([
                'ok' => false,
                'message' => 'No coordinates in the top result.',
            ], 404);
        }

        $lng = (float) $coords[0];
        $lat = (float) $coords[1];

        if ($lat < -90.0 || $lat > 90.0 || $lng < -180.0 || $lng > 180.0) {
            return response()->json([
                'ok' => false,
                'message' => 'Geocoder returned invalid coordinates.',
            ], 502);
        }

        $props = $first['properties'] ?? [];
        $label = '';
        if (is_array($props)) {
            $label = (string) ($props['place_name'] ?? $props['matching_place_name'] ?? $props['text'] ?? '');
        }

        return response()->json([
            'ok' => true,
            'latitude' => $lat,
            'longitude' => $lng,
            'label' => $label !== '' ? $label : $q,
        ]);
    }

    /**
     * MapLibre style JSON (MapTiler) with API keys stripped from all strings.
     * The browser must send follow-up requests through {@see proxy()} (e.g. via transformRequest).
     */
    public function style(): JsonResponse
    {
        $key = config('maptiler.api_key');
        if (! is_string($key) || $key === '') {
            abort(503, 'Map is not configured (missing MAPTILER_API_KEY).');
        }

        $styleId = (string) config('maptiler.map_style', 'streets-v2');
        $cacheKey = 'maptiler_admin_style_stripped_'.$styleId;

        $stripped = Cache::remember($cacheKey, 86400, function () use ($key, $styleId) {
            $url = sprintf('https://api.maptiler.com/maps/%s/style.json', rawurlencode($styleId));

            $response = Http::timeout(45)
                ->withHeaders([
                    'Referer' => (string) config('maptiler.http_referer', config('app.url')),
                ])
                ->get($url, ['key' => $key]);

            if (! $response->successful()) {
                abort(502, 'Map provider returned an error.');
            }

            $decoded = json_decode($response->body(), true);
            if (! is_array($decoded)) {
                abort(502, 'Invalid map style response.');
            }

            return $this->stripMaptilerKeysFromJson($decoded);
        });

        return response()->json($stripped)
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Proxy MapTiler resources (tiles, glyphs, sprites, …) without exposing the API key to the client.
     */
    public function proxy(Request $request): Response
    {
        $target = $request->query('target');
        if (! is_string($target) || $target === '') {
            abort(400);
        }

        $decoded = base64_decode($target, true);
        if ($decoded === false || $decoded === '') {
            abort(400);
        }

        if (! $this->isAllowedMaptilerProxyTarget($decoded)) {
            abort(403);
        }

        $key = config('maptiler.api_key');
        if (! is_string($key) || $key === '') {
            abort(503);
        }

        $upstream = $decoded;
        if (! preg_match('/[?&]key=/', $upstream)) {
            $upstream .= str_contains($upstream, '?') ? '&' : '?';
            $upstream .= 'key='.rawurlencode($key);
        }

        // MapTiler key "URL / HTTP referrer" restrictions apply to this outbound request.
        // Prefer the browser's Referer (the admin page actually being used) so APP_URL can differ
        // from the local vhost (e.g. APP_URL=localhost while browsing http://unbeatentrack.test).
        $referer = trim((string) $request->headers->get('Referer', ''));
        if ($referer === '') {
            $referer = trim((string) $request->headers->get('Origin', ''));
        }
        if ($referer === '') {
            $referer = (string) config('maptiler.http_referer', config('app.url', 'http://localhost'));
        }

        $response = Http::timeout(45)
            ->withHeaders([
                'Referer' => $referer,
                'User-Agent' => 'UnbeatenTrackMapProxy/1.0 (Laravel)',
            ])
            ->withOptions(['http_errors' => false])
            ->get($upstream);

        $out = response($response->body(), $response->status());

        $ct = $response->header('Content-Type');
        if (is_string($ct) && $ct !== '') {
            $out->headers->set('Content-Type', $ct);
        }

        if ($response->successful()) {
            $out->headers->set('Cache-Control', 'public, max-age=86400');
        }

        return $out;
    }

    /**
     * Only https targets on MapTiler hosts (prevents open proxy abuse).
     */
    private function isAllowedMaptilerProxyTarget(string $decoded): bool
    {
        $parts = parse_url($decoded);
        if (($parts['scheme'] ?? '') !== 'https') {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '') {
            return false;
        }

        if (in_array($host, ['api.maptiler.com', 'tiles.maptiler.com', 'maptiler.com'], true)) {
            return true;
        }

        return str_ends_with($host, '.maptiler.com');
    }

    /**
     * @param  array<mixed>  $node
     * @return array<mixed>|mixed
     */
    private function stripMaptilerKeysFromJson(mixed $node): mixed
    {
        if (is_string($node)) {
            $s = preg_replace('/([&?])key=[^&#]*/', '', $node) ?? $node;
            $s = rtrim($s, '?&');

            return $s;
        }

        if (is_array($node)) {
            foreach ($node as $k => $v) {
                $node[$k] = $this->stripMaptilerKeysFromJson($v);
            }

            return $node;
        }

        return $node;
    }
}
