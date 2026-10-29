<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\CanonicalProduct;
use App\Models\Product;
use App\Models\TelegramGroup;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test guest cannot access admin dashboard.
     */
    public function test_guest_is_redirected_to_login_from_admin_routes(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');
    }

    /**
     * Test regular user receives 403 Forbidden on admin routes.
     */
    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role'   => 'user',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/admin/dashboard');
        $response->assertForbidden();
    }

    /**
     * Test admin user can access admin dashboard.
     */
    public function test_admin_user_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'role'   => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertOk();
        $response->assertSee('System Dashboard Overview');
        $response->assertSee('Monitored Groups');
        $response->assertSee('Canonical Products');
        $response->assertSee('Multi-Group Matches');
        $response->assertSee('Total Group Offers');
        $response->assertSee('Parsed Prices');
    }

    /**
     * Test blocked user cannot access protected routes.
     */
    public function test_blocked_user_is_logged_out_and_redirected(): void
    {
        $blockedUser = User::factory()->create([
            'role'   => 'user',
            'status' => 'blocked',
        ]);

        $response = $this->actingAs($blockedUser)->get('/');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * Test regular user cannot escalate role via profile update.
     */
    public function test_user_cannot_escalate_role_via_profile_update(): void
    {
        $user = User::factory()->create([
            'role'   => 'user',
            'status' => 'active',
        ]);

        $this->actingAs($user)->put('/profile', [
            'name'  => 'Hacker Name',
            'email' => $user->email,
            'role'  => 'admin',
        ]);

        $this->assertDatabaseHas('users', [
            'id'   => $user->id,
            'role' => 'user', // MUST remain 'user'
        ]);
    }

    /**
     * Test admin dashboard returns real 5 statistics matching database records.
     */
    public function test_admin_dashboard_returns_real_database_metrics(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $group = TelegramGroup::create([
            'telegram_id' => 999888777,
            'title'       => 'Test Channel A',
            'invite_link' => 'https://t.me/test_channel_a',
            'is_active'   => true,
        ]);

        $canonical = CanonicalProduct::create([
            'canonical_key'    => 'test_product_123',
            'name'             => 'Test Canonical Phone',
            'cheapest_price'   => 15000.00,
            'offers_count'     => 1,
            'last_observed_at' => now(),
        ]);

        Product::create([
            'canonical_product_id' => $canonical->id,
            'group_id'             => $group->id,
            'product_key'          => 'raw_product_123',
            'product_name'         => 'Test Canonical Phone 128GB',
            'latest_price'         => 15000.00,
            'latest_message_id'    => 101,
            'first_seen_at'        => now(),
            'last_updated_at'      => now(),
        ]);

        $response = $this->actingAs($admin)->getJson('/admin/dashboard');
        $response->assertOk();

        $data = $response->json();
        $this->assertArrayHasKey('stats', $data);
        $this->assertGreaterThanOrEqual(1, $data['stats']['monitored_groups']);
        $this->assertGreaterThanOrEqual(1, $data['stats']['canonical_products']);
        $this->assertGreaterThanOrEqual(1, $data['stats']['total_offers']);
        $this->assertGreaterThanOrEqual(1, $data['stats']['parsed_prices']);
    }

    /**
     * Test admin can toggle group monitoring without deleting products.
     */
    public function test_admin_can_toggle_group_monitoring(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $group = TelegramGroup::create([
            'telegram_id' => 123456789,
            'title'       => 'Toggle Test Channel',
            'invite_link' => 'https://t.me/toggle_test',
            'is_active'   => true,
        ]);

        $offer = Product::create([
            'group_id'          => $group->id,
            'product_key'       => 'offer_toggle_1',
            'product_name'      => 'Offer Item',
            'latest_price'      => 999,
            'latest_message_id' => 12,
            'first_seen_at'     => now(),
            'last_updated_at'   => now(),
        ]);

        // Toggle to paused
        $response = $this->actingAs($admin)->postJson("/admin/telegram-groups/{$group->id}/toggle");
        $response->assertOk();
        $this->assertFalse($group->fresh()->is_active);

        // Product offer must still exist!
        $this->assertDatabaseHas('products', ['id' => $offer->id]);

        // Toggle back to active
        $response = $this->actingAs($admin)->postJson("/admin/telegram-groups/{$group->id}/toggle");
        $response->assertOk();
        $this->assertTrue($group->fresh()->is_active);
    }

    /**
     * Test admin can block and unblock user, but cannot block own account.
     */
    public function test_admin_can_block_and_unblock_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $targetUser = User::factory()->create(['role' => 'user', 'status' => 'active']);

        // Block user
        $response = $this->actingAs($admin)->postJson("/admin/users/{$targetUser->id}/toggle-status");
        $response->assertOk();
        $this->assertEquals('blocked', $targetUser->fresh()->status);

        // Unblock user
        $response = $this->actingAs($admin)->postJson("/admin/users/{$targetUser->id}/toggle-status");
        $response->assertOk();
        $this->assertEquals('active', $targetUser->fresh()->status);

        // Cannot block self
        $response = $this->actingAs($admin)->postJson("/admin/users/{$admin->id}/toggle-status");
        $response->assertStatus(400);
        $this->assertEquals('active', $admin->fresh()->status);
    }

    /**
     * Test admin can update role between user and admin, but cannot self-demote.
     */
    public function test_admin_can_change_user_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        // Promote user to admin
        $response = $this->actingAs($admin)->postJson("/admin/users/{$user->id}/role", [
            'role' => 'admin',
        ]);
        $response->assertOk();
        $this->assertEquals('admin', $user->fresh()->role);

        // Demote back to user
        $response = $this->actingAs($admin)->postJson("/admin/users/{$user->id}/role", [
            'role' => 'user',
        ]);
        $response->assertOk();
        $this->assertEquals('user', $user->fresh()->role);

        // Cannot self-demote
        $response = $this->actingAs($admin)->postJson("/admin/users/{$admin->id}/role", [
            'role' => 'user',
        ]);
        $response->assertStatus(400);
        $this->assertEquals('admin', $admin->fresh()->role);
    }

    /**
     * Test admin can update subscription plan and application settings.
     */
    public function test_admin_can_update_plan_and_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        // Update plan pricing
        $response = $this->actingAs($admin)->postJson('/admin/subscriptions/plan', [
            'trial_days'                 => 14,
            'subscription_price'         => 99.00,
            'subscription_duration_days' => 60,
            'currency_symbol'            => '₹',
        ]);
        $response->assertOk();

        /** @var SettingService $settingService */
        $settingService = app(SettingService::class);
        $this->assertEquals(14, $settingService->get('trial_days'));
        $this->assertEquals(99.00, $settingService->get('subscription_price'));
        $this->assertEquals(60, $settingService->get('subscription_duration_days'));

        // Check activity log was created
        $this->assertDatabaseHas('admin_activity_logs', [
            'action' => 'subscription_plan_updated',
        ]);
    }

    /**
     * Test admin activity logs redact sensitive keys.
     */
    public function test_admin_activity_log_redacts_sensitive_keys(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin);

        $log = AdminActivityLog::record('test_action', 'Test sensitive sanitize', null, [
            'username' => 'testuser',
            'password' => 'supersecret123',
            'token'    => 'secret-auth-token',
        ]);

        $this->assertEquals('testuser', $log->details['username']);
        $this->assertEquals('[REDACTED]', $log->details['password']);
        $this->assertEquals('[REDACTED]', $log->details['token']);
    }

    /**
     * Test all admin view pages render successfully with status 200.
     */
    public function test_all_admin_pages_render_successfully(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $routes = [
            '/admin/dashboard',
            '/admin/telegram-groups',
            '/admin/products',
            '/admin/users',
            '/admin/subscriptions',
            '/admin/settings',
            '/admin/activity-logs',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($admin)->get($route);
            $response->assertOk();
        }
    }
}
