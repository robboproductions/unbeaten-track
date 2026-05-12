<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MapController extends Controller
{
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

        if (! Str::startsWith($decoded, ['https://api.maptiler.com/', 'https://tiles.maptiler.com/'])) {
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

        $response = Http::timeout(45)
            ->withHeaders([
                'Referer' => (string) config('maptiler.http_referer', config('app.url')),
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
