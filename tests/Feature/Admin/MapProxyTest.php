<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MapProxyTest extends TestCase
{
    use RefreshDatabase;

    public function test_proxy_forwards_incoming_referer_to_maptiler(): void
    {
        config(['maptiler.api_key' => 'test-maptiler-key']);

        Http::fake([
            'https://api.maptiler.com/tiles/v3/0/0/0.pbf*' => Http::response("\x1a\x1a", 200, [
                'Content-Type' => 'application/vnd.mapbox-vector-tile',
            ]),
        ]);

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $target = base64_encode('https://api.maptiler.com/tiles/v3/0/0/0.pbf');

        $this->actingAs($user)
            ->withHeader('Referer', 'http://unbeatentrack.test/admin/dashboard')
            ->get(route('admin.maps.proxy', ['target' => $target]))
            ->assertOk();

        Http::assertSent(function (Request $request): bool {
            return str_starts_with($request->url(), 'https://api.maptiler.com/tiles/v3/0/0/0.pbf')
                && $request->hasHeader('Referer', 'http://unbeatentrack.test/admin/dashboard');
        });
    }

    public function test_proxy_rejects_non_maptiler_hosts(): void
    {
        config(['maptiler.api_key' => 'test-maptiler-key']);

        Http::fake();

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $target = base64_encode('https://example.com/tile.pbf');

        $this->actingAs($user)
            ->get(route('admin.maps.proxy', ['target' => $target]))
            ->assertForbidden();

        Http::assertNothingSent();
    }
}
