<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPoisMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_pois_map_page_loads_for_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($user)->get(route('admin.pois.map'))->assertOk();
    }
}
