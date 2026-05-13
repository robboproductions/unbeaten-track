<?php

namespace Tests\Feature\Admin;

use App\Models\Town;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TownNarrationScriptDraftTest extends TestCase
{
    use RefreshDatabase;

    private function makeTown(): Town
    {
        return Town::create([
            'name' => 'Riverbend',
            'state' => 'New South Wales',
            'region' => 'Central Tablelands',
            'status' => 'draft',
            'verification_status' => 'unverified',
            'editorial_hook' => 'A quiet stop on the river.',
        ]);
    }

    public function test_narration_script_draft_returns_503_without_provider_keys(): void
    {
        config([
            'town_ai.openai_api_key' => null,
            'town_ai.anthropic_api_key' => null,
        ]);

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $town = $this->makeTown();

        $this->actingAs($user)
            ->postJson(route('admin.towns.ai-narration-script-draft', $town))
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
                    ['type' => 'text', 'text' => "G'day! You're rolling into Riverbend — a good spot to stretch the legs."],
                ],
            ], 200),
        ]);

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $town = $this->makeTown();

        $response = $this->actingAs($user)
            ->postJson(route('admin.towns.ai-narration-script-draft', $town));

        $response->assertOk()
            ->assertJsonStructure(['script']);

        $script = $response->json('script');
        $this->assertStringNotContainsString("\u{2014}", $script);
        $this->assertStringContainsString('Riverbend', $script);
    }
}
