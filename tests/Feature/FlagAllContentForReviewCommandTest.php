<?php

namespace Tests\Feature;

use App\Enums\PoiVerificationStatus;
use App\Models\Poi;
use App\Models\Town;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlagAllContentForReviewCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_unpublishes_towns_and_flags_pois_pending_by_default(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $town = Town::create([
            'name' => 'Testville',
            'state' => 'New South Wales',
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $user->id,
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verified_by' => $user->id,
        ]);

        $poi = Poi::create([
            'name' => 'Test POI',
            'categories' => ['Deep Roots'],
            'town_id' => $town->id,
            'state' => 'New South Wales',
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $user->id,
            'verification_status' => PoiVerificationStatus::BeatenTrackVerified,
        ]);

        $this->artisan('content:flag-all-for-review', ['--force' => true])
            ->assertSuccessful();

        $town->refresh();
        $this->assertSame('pending', $town->status);
        $this->assertNull($town->published_at);
        $this->assertNull($town->published_by);
        $this->assertSame('unverified', $town->verification_status);
        $this->assertNull($town->verified_at);
        $this->assertNull($town->verified_by);

        $poi->refresh();
        $this->assertSame('pending', $poi->status);
        $this->assertNull($poi->published_at);
        $this->assertNull($poi->published_by);
        $this->assertSame(PoiVerificationStatus::NotVerified, $poi->verification_status);
    }

    public function test_pois_status_draft_option(): void
    {
        $town = Town::create([
            'name' => 'Other Town',
            'state' => 'Victoria',
            'status' => 'published',
            'verification_status' => 'verified',
        ]);
        $poi = Poi::create([
            'name' => 'Other POI',
            'categories' => ['Deep Roots'],
            'town_id' => $town->id,
            'state' => 'Victoria',
            'status' => 'published',
            'verification_status' => PoiVerificationStatus::CommunityVerified,
        ]);

        $this->artisan('content:flag-all-for-review', [
            '--force' => true,
            '--pois-status' => 'draft',
        ])->assertSuccessful();

        $town->refresh();
        $this->assertSame('pending', $town->status);

        $poi->refresh();
        $this->assertSame('draft', $poi->status);
        $this->assertSame(PoiVerificationStatus::NotVerified, $poi->verification_status);
    }

    public function test_towns_status_draft_option(): void
    {
        $town = Town::create([
            'name' => 'Draft Town',
            'state' => 'Victoria',
            'status' => 'published',
            'verification_status' => 'verified',
        ]);

        $this->artisan('content:flag-all-for-review', [
            '--force' => true,
            '--towns-status' => 'draft',
        ])->assertSuccessful();

        $town->refresh();
        $this->assertSame('draft', $town->status);
    }

    public function test_invalid_towns_status_fails(): void
    {
        $this->artisan('content:flag-all-for-review', [
            '--force' => true,
            '--towns-status' => 'published',
        ])->assertFailed();
    }

    public function test_invalid_pois_status_fails(): void
    {
        $this->artisan('content:flag-all-for-review', [
            '--force' => true,
            '--pois-status' => 'published',
        ])->assertFailed();
    }
}
