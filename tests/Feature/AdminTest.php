<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;



    public function test_admin_user_can_be_created(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertEquals('admin', $admin->role);
        $this->assertDatabaseHas('users', [
            'email' => $admin->email,
            'role' => 'admin',
        ]);
    }

    public function test_accountant_user_can_be_created(): void
    {
        $accountant = User::factory()->create(['role' => 'accountant']);

        $this->assertEquals('accountant', $accountant->role);
    }

    public function test_viewer_user_can_be_created(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $this->assertEquals('viewer', $viewer->role);
    }

    public function test_user_factory_defaults_to_accountant(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('accountant', $user->role);
    }

    public function test_user_factory_admin_state(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertEquals('admin', $admin->role);
    }

    public function test_user_factory_viewer_state(): void
    {
        $viewer = User::factory()->viewer()->create();

        $this->assertEquals('viewer', $viewer->role);
    }

    public function test_user_password_is_hashed(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('my-secret-password'),
        ]);

        // Password should not be stored as plain text
        $this->assertNotEquals('my-secret-password', $user->password);
    }

    public function test_multiple_users_with_different_roles(): void
    {
        $admin = User::factory()->admin()->create();
        $accountant1 = User::factory()->create();
        $accountant2 = User::factory()->create();
        $viewer = User::factory()->viewer()->create();

        $this->assertEquals(4, User::count());
        $this->assertEquals(1, User::where('role', 'admin')->count());
        $this->assertEquals(2, User::where('role', 'accountant')->count());
        $this->assertEquals(1, User::where('role', 'viewer')->count());
    }

    public function test_user_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'admin@test.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::factory()->create(['email' => 'admin@test.com']);
    }
}
