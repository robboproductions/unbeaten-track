<?php

namespace Tests\Feature\Admin;

use App\Enums\PoiVerificationStatus;
use App\Models\Poi;
use App\Models\Town;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardOverviewMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_includes_overview_map_payload_for_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response
            ->assertOk()
            ->assertViewHas('stats')
            ->assertViewHas('overviewMap', function (array $m): bool {
                return isset($m['enabled'], $m['townsGeojson'], $m['poisGeojson'])
                    && $m['townsGeojson']['type'] === 'FeatureCollection'
                    && $m['poisGeojson']['type'] === 'FeatureCollection';
            })
            ->assertSee('NSW', false);

        $html = $response->getContent();
        self::assertStringContainsString('data-dash-stat="towns"', $html);
        self::assertStringContainsString('data-dash-stat="pois"', $html);
        self::assertStringContainsString('data-dash-stat="pois-pending"', $html);
        self::assertStringContainsString('data-dash-stat="towns-pending"', $html);
        self::assertStringContainsString('data-dash-stat="pois-published"', $html);
        self::assertStringContainsString('data-dash-stat="towns-published"', $html);
    }

    public function test_overview_map_geojson_only_includes_mapped_nsw_vic_records(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $townNsw = Town::create([
            'name' => 'Sydney Side',
            'state' => 'New South Wales',
            'status' => 'draft',
            'verification_status' => 'unverified',
            'latitude' => -33.8688,
            'longitude' => 151.2093,
        ]);

        Town::create([
            'name' => 'Elsewhere',
            'state' => 'Queensland',
            'status' => 'draft',
            'verification_status' => 'unverified',
            'latitude' => -27.0,
            'longitude' => 153.0,
        ]);

        $this->actingAs($user);

        $poi = Poi::create([
            'name' => 'Coastal Walk',
            'categories' => ['Wild Places'],
            'town_id' => $townNsw->id,
            'state' => 'New South Wales',
            'status' => 'published',
            'verification_status' => PoiVerificationStatus::NotVerified,
            'latitude' => -33.87,
            'longitude' => 151.2,
        ]);

        $overview = $this->get(route('admin.dashboard'))
            ->assertOk()
            ->viewData('overviewMap');

        self::assertCount(1, $overview['townsGeojson']['features']);
        self::assertCount(1, $overview['poisGeojson']['features']);
        self::assertSame($townNsw->id, $overview['townsGeojson']['features'][0]['properties']['id']);
        self::assertSame($poi->id, $overview['poisGeojson']['features'][0]['properties']['id']);
    }
}
