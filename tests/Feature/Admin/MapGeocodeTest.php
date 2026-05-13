<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MapGeocodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_geocode_returns_top_result_from_maptiler(): void
    {
        config(['maptiler.api_key' => 'test-maptiler-key']);

        Http::fake([
            'https://api.maptiler.com/geocoding/*' => Http::response([
                'type' => 'FeatureCollection',
                'features' => [[
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [148.1234567, -35.9876543],
                    ],
                    'properties' => [
                        'place_name' => 'Example, NSW, Australia',
                    ],
                ]],
            ], 200),
        ]);

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)
            ->withHeader('Referer', 'http://unbeatentrack.test/admin/towns/create')
            ->postJson(route('admin.maps.geocode'), ['q' => 'Example town']);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('label', 'Example, NSW, Australia');

        $this->assertEqualsWithDelta(-35.9876543, (float) $response->json('latitude'), 0.0000001);
        $this->assertEqualsWithDelta(148.1234567, (float) $response->json('longitude'), 0.0000001);
    }

    public function test_geocode_requires_authentication(): void
    {
        config(['maptiler.api_key' => 'test-maptiler-key']);
        Http::fake();

        $this->postJson(route('admin.maps.geocode'), ['q' => 'Sydney'])
            ->assertUnauthorized();

        Http::assertNothingSent();
    }

    public function test_geocode_returns_503_when_maptiler_key_missing(): void
    {
        config(['maptiler.api_key' => '']);
        Http::fake();

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($user)
            ->postJson(route('admin.maps.geocode'), ['q' => 'Sydney'])
            ->assertStatus(503)
            ->assertJsonPath('ok', false);

        Http::assertNothingSent();
    }

    public function test_geocode_returns_404_when_no_features(): void
    {
        config(['maptiler.api_key' => 'test-maptiler-key']);

        Http::fake([
            'https://api.maptiler.com/geocoding/*' => Http::response([
                'type' => 'FeatureCollection',
                'features' => [],
            ], 200),
        ]);

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($user)
            ->postJson(route('admin.maps.geocode'), ['q' => 'zzzznonexistent12345'])
            ->assertStatus(404)
            ->assertJsonPath('ok', false);
    }
}
