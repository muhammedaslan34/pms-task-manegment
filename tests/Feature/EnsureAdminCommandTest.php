<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EnsureAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_admin_once_and_never_changes_an_existing_user(): void
    {
        config(['app.admin' => ['email' => 'boss@example.com', 'name' => 'Boss', 'password' => 'a-very-long-password']]);

        $this->artisan('app:ensure-admin')->assertSuccessful();

        $admin = User::where('email', 'boss@example.com')->sole();
        $this->assertTrue($admin->is_admin);
        $this->assertTrue(Hash::check('a-very-long-password', $admin->password));

        config(['app.admin.password' => 'another-long-password']);
        $this->artisan('app:ensure-admin')->assertSuccessful();

        $this->assertTrue(Hash::check('a-very-long-password', $admin->fresh()->password));
        $this->assertSame(1, User::count());
    }

    public function test_it_refuses_a_missing_or_short_password(): void
    {
        config(['app.admin' => ['email' => 'boss@example.com', 'name' => 'Boss', 'password' => 'short']]);

        $this->artisan('app:ensure-admin')->assertFailed();
        $this->assertSame(0, User::count());
    }
}
