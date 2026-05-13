<?php

namespace Tests\Feature\Admin;

use App\Enums\PoiVerificationStatus;
use App\Models\Poi;
use App\Models\Town;
use App\Models\User;
use App\Services\PoiNarrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PoiNarrationTest extends TestCase
{
    use RefreshDatabase;

    private function fakeMp3BinaryBody(): string
    {
        return "\xFF\xFB\x90\x00".str_repeat("\xAA", 120);
    }

    /**
     * @return array<string, mixed>
     */
    private function narrationVoicesTestConfig(): array
    {
        return [
            'poi_narration.voices' => [
                'baxter' => ['id' => 'voice-1', 'label' => 'Baxter'],
                'zoe' => ['id' => 'voice-2', 'label' => 'Zoe'],
            ],
        ];
    }

    private function makeTownAndPoi(): Poi
    {
        $town = Town::create([
            'name' => 'Testville',
            'state' => 'New South Wales',
            'status' => 'draft',
            'verification_status' => 'unverified',
        ]);

        return Poi::create([
            'name' => 'Test POI',
            'categories' => ['Deep Roots'],
            'town_id' => $town->id,
            'state' => 'New South Wales',
            'status' => 'draft',
            'verification_status' => PoiVerificationStatus::NotVerified,
            'narration_script' => 'Welcome to this stop on the track.',
        ]);
    }

    public function test_narration_audio_url_is_root_relative_for_same_host_playback(): void
    {
        $poi = new Poi;
        $poi->forceFill([
            'narration_audio_path' => 'poi-narrations/116-test.mp3',
            'narration_generated_at' => now(),
        ]);

        $this->assertStringStartsWith('/storage/poi-narrations/116-test.mp3?v=', (string) $poi->narration_audio_url);
    }

    public function test_non_admin_cannot_generate_narration(): void
    {
        $user = User::factory()->create(['role' => 'member']);
        $poi = $this->makeTownAndPoi();

        $this->actingAs($user)
            ->post(route('admin.pois.narration.generate', $poi))
            ->assertForbidden();
    }

    public function test_admin_can_generate_narration(): void
    {
        Storage::fake('public');
        config(array_merge($this->narrationVoicesTestConfig(), [
            'poi_narration.enabled' => true,
            'poi_narration.storage.disk' => 'public',
            'poi_narration.elevenlabs.api_key' => 'test-key',
            'poi_narration.elevenlabs.default_voice_id' => 'voice-1',
            'poi_narration.elevenlabs.default_model_id' => 'eleven_turbo_v2_5',
        ]));

        Http::fake([
            'https://api.elevenlabs.io/v1/text-to-speech/*' => Http::response($this->fakeMp3BinaryBody(), 200, ['Content-Type' => 'application/octet-stream']),
        ]);

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $poi = $this->makeTownAndPoi();

        $this->actingAs($user)
            ->from(route('admin.pois.edit', $poi))
            ->post(route('admin.pois.narration.generate', $poi), ['narration_voice_id' => 'voice-1'])
            ->assertRedirect(route('admin.pois.edit', $poi));

        $poi->refresh();
        $this->assertNotNull($poi->narration_audio_path);
        $this->assertTrue(Storage::disk('public')->exists($poi->narration_audio_path));
        $this->assertNotNull($poi->narration_script_hash);
        $this->assertSame('voice-1', $poi->narration_voice_id);
        $this->assertSame($user->id, $poi->narration_generated_by);
    }

    public function test_generation_fails_gracefully_on_provider_error(): void
    {
        Storage::fake('public');
        config(array_merge($this->narrationVoicesTestConfig(), [
            'poi_narration.enabled' => true,
            'poi_narration.storage.disk' => 'public',
            'poi_narration.elevenlabs.api_key' => 'test-key',
            'poi_narration.elevenlabs.default_voice_id' => 'voice-1',
        ]));

        Http::fake([
            'https://api.elevenlabs.io/v1/text-to-speech/*' => Http::response('upstream error', 500),
        ]);

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $poi = $this->makeTownAndPoi();

        $this->actingAs($user)
            ->from(route('admin.pois.edit', $poi))
            ->post(route('admin.pois.narration.generate', $poi), ['narration_voice_id' => 'voice-1'])
            ->assertRedirect(route('admin.pois.edit', $poi))
            ->assertSessionHasErrors('narration');

        $this->assertSame(0, count(Storage::disk('public')->allFiles()));
    }

    public function test_generation_rejects_non_mp3_payload_with_200(): void
    {
        Storage::fake('public');
        config(array_merge($this->narrationVoicesTestConfig(), [
            'poi_narration.enabled' => true,
            'poi_narration.storage.disk' => 'public',
            'poi_narration.elevenlabs.api_key' => 'test-key',
            'poi_narration.elevenlabs.default_voice_id' => 'voice-1',
        ]));

        Http::fake([
            'https://api.elevenlabs.io/v1/text-to-speech/*' => Http::response(str_repeat('Z', 400), 200, ['Content-Type' => 'application/octet-stream']),
        ]);

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $poi = $this->makeTownAndPoi();

        $this->actingAs($user)
            ->from(route('admin.pois.edit', $poi))
            ->post(route('admin.pois.narration.generate', $poi), ['narration_voice_id' => 'voice-1'])
            ->assertSessionHasErrors('narration');

        $this->assertSame(0, count(Storage::disk('public')->allFiles()));
    }

    public function test_generation_rejects_id3_metadata_without_mpeg_audio(): void
    {
        Storage::fake('public');
        config(array_merge($this->narrationVoicesTestConfig(), [
            'poi_narration.enabled' => true,
            'poi_narration.storage.disk' => 'public',
            'poi_narration.elevenlabs.api_key' => 'test-key',
            'poi_narration.elevenlabs.default_voice_id' => 'voice-1',
        ]));

        $id3Payload = str_repeat("\x00", 120);
        $id3 = "ID3\x04\x00\x00\x00\x00\x00\x78".$id3Payload;

        Http::fake([
            'https://api.elevenlabs.io/v1/text-to-speech/*' => Http::response($id3, 200, ['Content-Type' => 'application/octet-stream']),
        ]);

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $poi = $this->makeTownAndPoi();

        $this->actingAs($user)
            ->from(route('admin.pois.edit', $poi))
            ->post(route('admin.pois.narration.generate', $poi), ['narration_voice_id' => 'voice-1'])
            ->assertSessionHasErrors('narration');

        $this->assertSame(0, count(Storage::disk('public')->allFiles()));
    }

    public function test_regeneration_replaces_old_file(): void
    {
        Storage::fake('public');
        config(array_merge($this->narrationVoicesTestConfig(), [
            'poi_narration.enabled' => true,
            'poi_narration.storage.disk' => 'public',
            'poi_narration.elevenlabs.api_key' => 'test-key',
            'poi_narration.elevenlabs.default_voice_id' => 'voice-1',
            'poi_narration.elevenlabs.default_model_id' => 'eleven_turbo_v2_5',
        ]));

        Http::fake([
            'https://api.elevenlabs.io/v1/text-to-speech/*' => Http::response($this->fakeMp3BinaryBody(), 200, ['Content-Type' => 'application/octet-stream']),
        ]);

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $poi = $this->makeTownAndPoi();

        $this->actingAs($user)->post(route('admin.pois.narration.generate', $poi), ['narration_voice_id' => 'voice-1']);
        $poi->refresh();
        $firstPath = $poi->narration_audio_path;
        $this->assertNotNull($firstPath);

        $poi->update(['narration_script' => 'A different script for the ear.']);

        $this->actingAs($user)->post(route('admin.pois.narration.generate', $poi), ['narration_voice_id' => 'voice-1']);
        $poi->refresh();
        $secondPath = $poi->narration_audio_path;

        $this->assertNotSame($firstPath, $secondPath);
        $this->assertFalse(Storage::disk('public')->exists((string) $firstPath));
        $this->assertTrue(Storage::disk('public')->exists((string) $secondPath));
    }

    public function test_empty_script_is_rejected(): void
    {
        Storage::fake('public');
        config(array_merge($this->narrationVoicesTestConfig(), [
            'poi_narration.enabled' => true,
            'poi_narration.storage.disk' => 'public',
            'poi_narration.elevenlabs.api_key' => 'test-key',
            'poi_narration.elevenlabs.default_voice_id' => 'voice-1',
        ]));

        Http::fake();

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $poi = $this->makeTownAndPoi();
        $poi->update(['narration_script' => null]);

        $this->actingAs($user)
            ->from(route('admin.pois.edit', $poi))
            ->post(route('admin.pois.narration.generate', $poi), ['narration_voice_id' => 'voice-1'])
            ->assertSessionHasErrors('narration');

        Http::assertNothingSent();
    }

    public function test_script_over_max_length_is_rejected(): void
    {
        Storage::fake('public');
        config(array_merge($this->narrationVoicesTestConfig(), [
            'poi_narration.enabled' => true,
            'poi_narration.storage.disk' => 'public',
            'poi_narration.limits.max_script_characters' => 100,
            'poi_narration.elevenlabs.api_key' => 'test-key',
            'poi_narration.elevenlabs.default_voice_id' => 'voice-1',
        ]));

        Http::fake();

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $poi = $this->makeTownAndPoi();
        $poi->update(['narration_script' => str_repeat('a', 101)]);

        $this->actingAs($user)
            ->post(route('admin.pois.narration.generate', $poi), ['narration_voice_id' => 'voice-1'])
            ->assertSessionHasErrors('narration');

        Http::assertNothingSent();
    }

    public function test_destroy_deletes_audio_file_and_clears_fields(): void
    {
        Storage::fake('public');
        config(array_merge($this->narrationVoicesTestConfig(), [
            'poi_narration.enabled' => true,
            'poi_narration.storage.disk' => 'public',
            'poi_narration.elevenlabs.api_key' => 'test-key',
            'poi_narration.elevenlabs.default_voice_id' => 'voice-1',
            'poi_narration.elevenlabs.default_model_id' => 'eleven_turbo_v2_5',
        ]));

        Http::fake([
            'https://api.elevenlabs.io/v1/text-to-speech/*' => Http::response($this->fakeMp3BinaryBody(), 200, ['Content-Type' => 'application/octet-stream']),
        ]);

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $poi = $this->makeTownAndPoi();

        $this->actingAs($user)->post(route('admin.pois.narration.generate', $poi), ['narration_voice_id' => 'voice-1']);
        $poi->refresh();
        $path = $poi->narration_audio_path;
        $this->assertNotNull($path);

        $this->actingAs($user)
            ->from(route('admin.pois.edit', $poi))
            ->delete(route('admin.pois.narration.destroy', $poi))
            ->assertRedirect(route('admin.pois.edit', $poi));

        $poi->refresh();
        $this->assertNull($poi->narration_audio_path);
        $this->assertFalse(Storage::disk('public')->exists((string) $path));
        $this->assertNotNull($poi->narration_script);
    }

    public function test_narration_is_stale_when_script_changes(): void
    {
        $poi = $this->makeTownAndPoi();
        $poi->forceFill([
            'narration_voice_id' => 'voice-1',
            'narration_model_id' => 'eleven_turbo_v2_5',
            'narration_audio_path' => 'poi-narrations/1-abc.mp3',
            'narration_script_hash' => hash('sha256', 'Welcome to this stop on the track.|voice-1|eleven_turbo_v2_5'),
            'narration_generated_at' => now(),
        ])->save();

        $service = app(PoiNarrationService::class);
        $this->assertFalse($service->isStale($poi->fresh()));

        $poi->update(['narration_script' => 'Changed words.']);
        $this->assertTrue($service->isStale($poi->fresh()));
    }

    public function test_poi_delete_removes_narration_file(): void
    {
        Storage::fake('public');
        config(['poi_narration.storage.disk' => 'public']);

        $poi = $this->makeTownAndPoi();
        Storage::disk('public')->put('poi-narrations/99-test.mp3', 'x');
        $poi->forceFill([
            'narration_audio_path' => 'poi-narrations/99-test.mp3',
        ])->save();

        $poi->delete();

        $this->assertFalse(Storage::disk('public')->exists('poi-narrations/99-test.mp3'));
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }
}
