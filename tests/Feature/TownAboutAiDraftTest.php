<?php

namespace Tests\Feature;

use App\Models\Town;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TownAboutAiDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_about_draft_returns_503_when_no_provider_keys(): void
    {
        config([
            'town_ai.openai_api_key' => null,
            'town_ai.anthropic_api_key' => null,
        ]);

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $town = Town::create([
            'name' => 'Testville',
            'state' => 'New South Wales',
            'status' => 'draft',
            'verification_status' => 'unverified',
        ]);

        $this->actingAs($user)
            ->postJson(route('admin.towns.ai-about-draft', $town))
            ->assertStatus(503)
            ->assertJsonPath('message', 'Add OPENAI_API_KEY and/or ANTHROPIC_API_KEY to .env to enable AI drafting.');
    }
}
