<?php

namespace Tests\Feature\Admin;

use App\Enums\PoiVerificationStatus;
use App\Models\Poi;
use App\Models\Town;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NarrationListTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_narrations_list(): void
    {
        $this->get(route('admin.narrations.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_panel_user_cannot_view_narrations_list(): void
    {
        $user = User::factory()->create(['role' => 'member']);

        $this->actingAs($user)
            ->get(route('admin.narrations.index'))
            ->assertForbidden();
    }

    public function test_admin_sees_narration_rows_sorted_newest_first(): void
    {
        config([
            'poi_narration.voices' => [
                'baxter' => ['id' => 'voice-1', 'label' => 'Baxter'],
            ],
        ]);

        $town = Town::create([
            'name' => 'Alpha Town',
            'state' => 'New South Wales',
            'status' => 'draft',
            'verification_status' => 'unverified',
        ]);
        $town->forceFill([
            'narration_audio_path' => 'town-narrations/1-old.mp3',
            'narration_generated_at' => now()->subDay(),
            'narration_voice_id' => 'voice-1',
            'narration_voice_label' => 'Baxter',
        ])->save();

        $poiTown = Town::create([
            'name' => 'Bravo Shire',
            'state' => 'Victoria',
            'status' => 'draft',
            'verification_status' => 'unverified',
        ]);
        $poi = Poi::create([
            'name' => 'Gamma POI',
            'categories' => ['Deep Roots'],
            'town_id' => $poiTown->id,
            'state' => 'Victoria',
            'status' => 'draft',
            'verification_status' => PoiVerificationStatus::NotVerified,
        ]);
        $poi->forceFill([
            'narration_audio_path' => 'poi-narrations/2-new.mp3',
            'narration_generated_at' => now(),
            'narration_voice_id' => 'voice-1',
            'narration_voice_label' => null,
        ])->save();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)
            ->get(route('admin.narrations.index'))
            ->assertOk();

        $content = $response->getContent();
        $poiPos = strpos($content, 'Gamma POI');
        $townPos = strpos($content, 'Alpha Town');
        $this->assertNotFalse($poiPos);
        $this->assertNotFalse($townPos);
        $this->assertLessThan($townPos, $poiPos, 'Newer POI row should appear before older town row');

        $response->assertSee('Baxter', false)
            ->assertSee('Bravo Shire', false)
            ->assertSee(route('admin.pois.edit', $poi), false)
            ->assertSee(route('admin.towns.edit', $town), false);
    }
}
