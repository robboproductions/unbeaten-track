<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTownsMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_towns_map_page_loads_for_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($user)->get(route('admin.towns.map'))->assertOk();
    }
}
