<?php

namespace Tests\Feature\Admin;

use App\Enums\PoiVerificationStatus;
use App\Models\Poi;
use App\Models\Town;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PoiNarrationScriptDraftTest extends TestCase
{
    use RefreshDatabase;

    private function makePoi(): Poi
    {
        $town = Town::create([
            'name' => 'Testville',
            'state' => 'New South Wales',
            'status' => 'draft',
            'verification_status' => 'unverified',
        ]);

        return Poi::create([
            'name' => 'Big Rock',
            'categories' => ['Wild Places'],
            'town_id' => $town->id,
            'state' => 'New South Wales',
            'status' => 'draft',
            'verification_status' => PoiVerificationStatus::NotVerified,
            'short_description' => 'A scenic lookout.',
        ]);
    }

    public function test_narration_script_draft_returns_503_without_provider_keys(): void
    {
        config([
            'town_ai.openai_api_key' => null,
            'town_ai.anthropic_api_key' => null,
        ]);

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $poi = $this->makePoi();

        $this->actingAs($user)
            ->postJson(route('admin.pois.ai-narration-script-draft', $poi))
            ->assertStatus(503);
    }

    public function test_narration_script_draft_returns_json_script(): void
    {
        config([
            'town_ai.provider' => 'anthropic',
            'town_ai.anthropic_api_key' => 'fake-key',
            'town_ai.anthropic_model' => 'claude-haiku-4-5',
        ]);

        Http::fake([
            'api.anthropic.com/v1/messages' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => "G'day! Pull up here — we'll tell you more in a moment."],
                ],
            ], 200),
        ]);

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $poi = $this->makePoi();

        $response = $this->actingAs($user)
            ->postJson(route('admin.pois.ai-narration-script-draft', $poi));

        $response->assertOk()
            ->assertJsonStructure(['script']);

        $script = $response->json('script');
        $this->assertStringNotContainsString("\u{2014}", $script);
        $this->assertStringContainsString('Pull up here', $script);
    }
}
