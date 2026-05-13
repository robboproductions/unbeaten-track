<?php

namespace Tests\Feature\Admin;

use App\Enums\PoiVerificationStatus;
use App\Models\Poi;
use App\Models\Town;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoiIndexNarrationFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_narration_filter_excludes_empty_string_path(): void
    {
        $town = Town::create([
            'name' => 'Testville',
            'state' => 'New South Wales',
            'status' => 'draft',
            'verification_status' => 'unverified',
        ]);

        $emptyPath = Poi::create([
            'name' => 'POI Empty Path',
            'categories' => ['Deep Roots'],
            'town_id' => $town->id,
            'state' => 'New South Wales',
            'status' => 'draft',
            'verification_status' => PoiVerificationStatus::NotVerified,
        ]);
        $emptyPath->forceFill(['narration_audio_path' => ''])->save();

        $withAudio = Poi::create([
            'name' => 'POI With Audio',
            'categories' => ['Deep Roots'],
            'town_id' => $town->id,
            'state' => 'New South Wales',
            'status' => 'draft',
            'verification_status' => PoiVerificationStatus::NotVerified,
        ]);
        $withAudio->forceFill(['narration_audio_path' => 'poi-narrations/1-test.mp3'])->save();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.pois.index', ['narration' => 'has']))
            ->assertOk()
            ->assertSee('POI With Audio', false)
            ->assertDontSee('POI Empty Path', false);

        $this->actingAs($admin)
            ->get(route('admin.pois.index', ['narration' => 'missing']))
            ->assertOk()
            ->assertSee('POI Empty Path', false);
    }
}
