<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_redirects_guests_to_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_user_can_sign_in_and_reach_dashboard(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Editor',
            'last_name' => 'User',
            'email' => 'editor@example.test',
            'password' => 'correct horse battery staple',
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => 'editor@example.test',
            'password' => 'correct horse battery staple',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_without_admin_panel_role_cannot_complete_sign_in(): void
    {
        User::factory()->create([
            'first_name' => 'Blocked',
            'last_name' => 'User',
            'email' => 'blocked@example.test',
            'password' => 'correct horse battery staple',
            'role' => 'no_access_role',
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => 'blocked@example.test',
            'password' => 'correct horse battery staple',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
