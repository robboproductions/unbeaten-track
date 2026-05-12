<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersAndProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_admin_cannot_access_user_management(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_super_admin_can_access_user_index(): void
    {
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)->get(route('admin.users.index'))->assertOk();
    }

    public function test_user_can_update_profile_without_password_change(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Pat',
            'last_name' => 'Lee',
            'email' => 'pat@example.test',
            'password' => 'existing-password-here',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($user)->patch(route('admin.profile.update'), [
            'first_name' => 'Patricia',
            'last_name' => 'Lee',
            'email' => 'patricia@example.test',
        ])->assertRedirect(route('admin.profile.edit'));

        $user->refresh();
        $this->assertSame('Patricia', $user->first_name);
        $this->assertSame('patricia@example.test', $user->email);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('existing-password-here', $user->password));
    }

    public function test_user_can_change_password_with_current_password(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Sam',
            'last_name' => 'Case',
            'email' => 'sam@example.test',
            'password' => 'old-secret-9',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($user)->patch(route('admin.profile.update'), [
            'first_name' => 'Sam',
            'last_name' => 'Case',
            'email' => 'sam@example.test',
            'current_password' => 'old-secret-9',
            'password' => 'new-secret-9x',
            'password_confirmation' => 'new-secret-9x',
        ])->assertRedirect(route('admin.profile.edit'));

        $user->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('new-secret-9x', $user->password));
    }
}
