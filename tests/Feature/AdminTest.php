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
     * Test guest is redirected to dedicated admin login from admin routes.
     */
    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/admin/login');
    }

    /**
     * Test regular user attempting to access admin route is cleanly logged out and redirected to admin login.
     */
    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role'   => 'user',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/admin/dashboard');
        $response->assertRedirect('/admin/login');
        $this->assertGuest('admin');
    }

    /**
     * Test admin login page renders successfully.
     */
    public function test_admin_login_page_renders_successfully(): void
    {
        $response = $this->get('/admin/login');
        $response->assertOk();
        $response->assertSee('Administrator Portal');
        $response->assertSee('Control Panel Login');
    }

    /**
     * Test administrator can authenticate via dedicated admin login.
     */
    public function test_administrator_can_authenticate_via_admin_login(): void
    {
        $admin = User::factory()->create([
            'email'    => 'admin_test@example.com',
            'password' => bcrypt('adminsecret123'),
            'role'     => 'admin',
            'status'   => 'active',
        ]);

        $response = $this->post('/admin/login', [
            'email'    => 'admin_test@example.com',
            'password' => 'adminsecret123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    /**
     * Test regular user cannot log in via dedicated admin login.
     */
    public function test_regular_user_cannot_log_in_via_admin_login(): void
    {
        $user = User::factory()->create([
            'email'    => 'customer@example.com',
            'password' => bcrypt('customerpass123'),
            'role'     => 'user',
            'status'   => 'active',
        ]);

        $response = $this->post('/admin/login', [
            'email'    => 'customer@example.com',
            'password' => 'customerpass123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
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
     * Test admin can update all 4 multi-tier plans with custom prices and discount badges.
     */
    public function test_admin_can_update_multi_tier_subscription_plans(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $response = $this->actingAs($admin)->post('/admin/subscriptions/plan', [
            'trial_days'      => 30,
            'currency_symbol' => '₹',
            'plans'           => [
                '1_month' => [
                    'name'          => '1 Month Starter',
                    'duration_days' => 30,
                    'amount'        => 59.00,
                    'badge'         => 'Starter',
                ],
                '4_month' => [
                    'name'          => '4 Months Special',
                    'duration_days' => 120,
                    'amount'        => 199.00,
                    'badge'         => 'Special 15% Off',
                ],
                '6_month' => [
                    'name'          => '6 Months Super Saver',
                    'duration_days' => 180,
                    'amount'        => 279.00,
                    'badge'         => '🔥 Super Deal',
                ],
                '1_year' => [
                    'name'          => '1 Year VIP Pass',
                    'duration_days' => 365,
                    'amount'        => 499.00,
                    'badge'         => '⭐ VIP 30% Off',
                ],
            ],
        ]);

        $response->assertRedirect();

        /** @var SettingService $settingService */
        $settingService = app(SettingService::class);
        $plans = $settingService->getSubscriptionPlans();

        $this->assertEquals('1 Month Starter', $plans['1_month']['name']);
        $this->assertEquals(59.00, $plans['1_month']['amount']);
        $this->assertEquals('Special 15% Off', $plans['4_month']['badge']);
        $this->assertEquals(279.00, $plans['6_month']['amount']);
        $this->assertEquals('1 Year VIP Pass', $plans['1_year']['name']);
        $this->assertEquals(499.00, $plans['1_year']['amount']);

        // Check index view displays the configured plans
        $indexResponse = $this->actingAs($admin)->get('/admin/subscriptions');
        $indexResponse->assertOk()
            ->assertSee('1 Month Starter')
            ->assertSee('Special 15% Off')
            ->assertSee('1 Year VIP Pass');
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
            '/admin/sent-links',
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

    /**
     * Test regular user does not see admin panel link, add group, or sync buttons on board.
     */
    public function test_regular_user_does_not_see_admin_controls_on_board(): void
    {
        $user = User::factory()->create([
            'role'          => 'user',
            'status'        => 'active',
            'trial_ends_at' => now()->addDays(20),
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertOk();
        $response->assertDontSee('Admin Panel');
        $response->assertDontSee('Add Group');
        $response->assertDontSee('Sync All History');
        $response->assertSee('Free Trial');
        $response->assertSee('My Subscription');
    }

    /**
     * Test admin user sees admin panel link, add group, and sync buttons on board.
     */
    public function test_admin_user_sees_admin_controls_on_board(): void
    {
        $admin = User::factory()->create([
            'role'   => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get('/');
        $response->assertOk();
        $response->assertSee('Admin Panel');
        $response->assertSee('Add Group');
        $response->assertSee('Sync All History');
    }

    /**
     * Test regular user cannot invoke group creation or sync APIs.
     */
    public function test_regular_user_cannot_call_group_management_apis(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $resStore = $this->actingAs($user)->postJson('/api/groups', ['link' => 'https://t.me/test_ch']);
        $resStore->assertForbidden();

        $resSyncAll = $this->actingAs($user)->postJson('/api/groups/sync-all');
        $resSyncAll->assertForbidden();
    }

    /**
     * Test TokenMismatchException redirects gracefully instead of raw 419 error.
     */
    public function test_token_mismatch_exception_is_handled_gracefully(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin);

        $request = \Illuminate\Http\Request::create('/admin/users/1/role', 'POST');
        $request->headers->set('referer', 'http://localhost/admin/users');
        $request->setLaravelSession(app('session.store'));
        app('url')->setRequest($request);

        $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $response = $handler->render($request, new \Symfony\Component\HttpKernel\Exception\HttpException(419, 'CSRF token mismatch.'));

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('http://localhost/admin/users', $response->getTargetUrl());
    }

    /**
     * Test admin can update group supplier details (phone, location, address).
     */
    public function test_admin_can_update_group_supplier_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $group = TelegramGroup::create([
            'telegram_id' => 888777666,
            'title'       => 'Supplier Details Test Group',
            'invite_link' => 'https://t.me/supplier_test',
            'is_active'   => true,
        ]);

        $response = $this->actingAs($admin)->putJson("/admin/telegram-groups/{$group->id}", [
            'title'       => 'Updated Supplier Hub',
            'invite_link' => 'https://t.me/updated_hub',
            'phone'       => '+91 98250 12345',
            'location'    => 'Surat Textile Market',
            'address'     => 'Shop 204, Millennium Ring Road',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('telegram_groups', [
            'id'       => $group->id,
            'phone'    => '+91 98250 12345',
            'location' => 'Surat Textile Market',
            'address'  => 'Shop 204, Millennium Ring Road',
        ]);
    }

    /**
     * Test board canonical product endpoint returns supplier details in offers.
     */
    public function test_board_canonical_product_api_returns_supplier_details(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $group = TelegramGroup::create([
            'telegram_id' => 555444333,
            'title'       => 'API Supplier Group',
            'invite_link' => 'https://t.me/api_supplier',
            'phone'       => '+91 99999 88888',
            'location'    => 'Delhi Wholesale Hub',
            'address'     => 'Sadar Bazaar, Delhi',
            'is_active'   => true,
        ]);

        $canonical = CanonicalProduct::create([
            'canonical_key'    => 'api_supplier_prod_key',
            'name'             => 'API Supplier Product',
            'cheapest_price'   => 450.00,
            'offers_count'     => 1,
            'last_observed_at' => now(),
        ]);

        Product::create([
            'canonical_product_id' => $canonical->id,
            'group_id'             => $group->id,
            'product_key'          => 'api_offer_raw_key',
            'product_name'         => 'API Supplier Product Offer',
            'latest_price'         => 450.00,
            'latest_message_id'    => 201,
            'first_seen_at'        => now(),
            'last_updated_at'      => now(),
        ]);

        $response = $this->actingAs($user)->getJson("/api/canonical-products/{$canonical->id}");
        $response->assertOk();

        $data = $response->json();
        $this->assertNotEmpty($data['product']['offers']);
        $firstOffer = $data['product']['offers'][0];
        $this->assertEquals('+91 99999 88888', $firstOffer['group_phone']);
        $this->assertEquals('Delhi Wholesale Hub', $firstOffer['group_location']);
        $this->assertEquals('Sadar Bazaar, Delhi', $firstOffer['group_address']);
    }
}
