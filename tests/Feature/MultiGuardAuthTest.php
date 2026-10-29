<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class MultiGuardAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_user_sessions_are_completely_independent(): void
    {
        $user = User::factory()->create([
            'email'    => 'customer@example.com',
            'password' => bcrypt('userpass123'),
            'role'     => 'user',
            'status'   => 'active',
        ]);

        $admin = User::factory()->create([
            'email'    => 'admin@telegram.local',
            'password' => bcrypt('adminpass123'),
            'role'     => 'admin',
            'status'   => 'active',
        ]);

        // 1. User logs in via standard user login
        $userLoginResponse = $this->post('/login', [
            'email'    => 'customer@example.com',
            'password' => 'userpass123',
        ]);
        $userLoginResponse->assertRedirect('/');
        $this->assertAuthenticatedAs($user, 'web');

        // 2. Admin logs in via dedicated admin login
        $adminLoginResponse = $this->post('/admin/login', [
            'email'    => 'admin@telegram.local',
            'password' => 'adminpass123',
        ]);
        $adminLoginResponse->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');

        // 3. User Dashboard still has the regular user authenticated on web guard
        $boardResponse = $this->get('/');
        $boardResponse->assertOk();
        $this->assertSame($user->id, Auth::guard('web')->id());
        $this->assertNotSame($admin->id, Auth::guard('web')->id());

        // 4. Admin Dashboard has the admin authenticated on admin guard
        $adminResponse = $this->get('/admin/dashboard');
        $adminResponse->assertOk();
        $this->assertSame($admin->id, Auth::guard('admin')->id());

        // 5. Admin logs out of Admin panel
        $logoutResponse = $this->post('/admin/logout');
        $logoutResponse->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');

        // 6. User is STILL logged in on User Dashboard!
        $this->assertAuthenticatedAs($user, 'web');
        $this->get('/')->assertOk();
    }
}
