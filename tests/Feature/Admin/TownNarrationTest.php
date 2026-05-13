<?php

namespace Tests\Feature\Admin;

use App\Models\Town;
use App\Models\User;
use App\Services\TownNarrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TownNarrationTest extends TestCase
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
                'terry' => ['id' => 'voice-1', 'label' => 'Terry'],
                'sarah' => ['id' => 'voice-2', 'label' => 'Sarah'],
            ],
        ];
    }

    private function makeTown(): Town
    {
        return Town::create([
            'name' => 'Testville',
            'state' => 'New South Wales',
            'status' => 'draft',
            'verification_status' => 'unverified',
            'narration_script' => 'Welcome to this town on the track.',
        ]);
    }

    public function test_narration_audio_url_is_root_relative_for_same_host_playback(): void
    {
        $town = new Town;
        $town->forceFill([
            'narration_audio_path' => 'town-narrations/12-test.mp3',
            'narration_generated_at' => now(),
        ]);

        $this->assertStringStartsWith('/storage/town-narrations/12-test.mp3?v=', (string) $town->narration_audio_url);
    }

    public function test_non_admin_cannot_generate_narration(): void
    {
        $user = User::factory()->create(['role' => 'member']);
        $town = $this->makeTown();

        $this->actingAs($user)
            ->post(route('admin.towns.narration.generate', $town))
            ->assertForbidden();
    }

    public function test_admin_can_generate_narration(): void
    {
        Storage::fake('public');
        config(array_merge($this->narrationVoicesTestConfig(), [
            'poi_narration.enabled' => true,
            'poi_narration.town_storage.disk' => 'public',
            'poi_narration.elevenlabs.api_key' => 'test-key',
            'poi_narration.elevenlabs.default_voice_id' => 'voice-1',
            'poi_narration.elevenlabs.default_model_id' => 'eleven_turbo_v2_5',
        ]));

        Http::fake([
            'https://api.elevenlabs.io/v1/text-to-speech/*' => Http::response($this->fakeMp3BinaryBody(), 200, ['Content-Type' => 'application/octet-stream']),
        ]);

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $town = $this->makeTown();

        $this->actingAs($user)
            ->from(route('admin.towns.edit', $town))
            ->post(route('admin.towns.narration.generate', $town), ['narration_voice_id' => 'voice-1'])
            ->assertRedirect(route('admin.towns.edit', $town));

        $town->refresh();
        $this->assertNotNull($town->narration_audio_path);
        $this->assertTrue(Storage::disk('public')->exists($town->narration_audio_path));
        $this->assertNotNull($town->narration_script_hash);
        $this->assertSame('voice-1', $town->narration_voice_id);
        $this->assertSame('Terry', $town->narration_voice_label);
        $this->assertSame($user->id, $town->narration_generated_by);
    }

    public function test_destroy_deletes_audio_file_and_clears_fields(): void
    {
        Storage::fake('public');
        config(array_merge($this->narrationVoicesTestConfig(), [
            'poi_narration.enabled' => true,
            'poi_narration.town_storage.disk' => 'public',
            'poi_narration.elevenlabs.api_key' => 'test-key',
            'poi_narration.elevenlabs.default_voice_id' => 'voice-1',
            'poi_narration.elevenlabs.default_model_id' => 'eleven_turbo_v2_5',
        ]));

        Http::fake([
            'https://api.elevenlabs.io/v1/text-to-speech/*' => Http::response($this->fakeMp3BinaryBody(), 200, ['Content-Type' => 'application/octet-stream']),
        ]);

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $town = $this->makeTown();

        $this->actingAs($user)->post(route('admin.towns.narration.generate', $town), ['narration_voice_id' => 'voice-1']);
        $town->refresh();
        $path = $town->narration_audio_path;
        $this->assertNotNull($path);

        $this->actingAs($user)
            ->from(route('admin.towns.edit', $town))
            ->delete(route('admin.towns.narration.destroy', $town))
            ->assertRedirect(route('admin.towns.edit', $town));

        $town->refresh();
        $this->assertNull($town->narration_audio_path);
        $this->assertNull($town->narration_voice_label);
        $this->assertFalse(Storage::disk('public')->exists((string) $path));
        $this->assertNotNull($town->narration_script);
    }

    public function test_narration_is_stale_when_script_changes(): void
    {
        $town = $this->makeTown();
        $town->forceFill([
            'narration_voice_id' => 'voice-1',
            'narration_model_id' => 'eleven_turbo_v2_5',
            'narration_audio_path' => 'town-narrations/1-abc.mp3',
            'narration_script_hash' => hash('sha256', 'Welcome to this town on the track.|voice-1|eleven_turbo_v2_5'),
            'narration_generated_at' => now(),
        ])->save();

        $service = app(TownNarrationService::class);
        $this->assertFalse($service->isStale($town->fresh()));

        $town->update(['narration_script' => 'Changed words.']);
        $this->assertTrue($service->isStale($town->fresh()));
    }

    public function test_town_delete_removes_narration_file(): void
    {
        Storage::fake('public');
        config(['poi_narration.town_storage.disk' => 'public']);

        $town = $this->makeTown();
        Storage::disk('public')->put('town-narrations/99-test.mp3', 'x');
        $town->forceFill([
            'narration_audio_path' => 'town-narrations/99-test.mp3',
        ])->save();

        $town->delete();

        $this->assertFalse(Storage::disk('public')->exists('town-narrations/99-test.mp3'));
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }
}
