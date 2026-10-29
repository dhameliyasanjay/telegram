<?php

namespace Tests\Feature;

use App\Models\SubscriptionOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_submit_subscription_order(): void
    {
        $user = User::factory()->create([
            'role'  => 'user',
            'phone' => null,
        ]);

        $response = $this->actingAs($user)->postJson(route('subscription.purchase'), [
            'name'           => 'Ramesh Patel',
            'phone'          => '9825199999',
            'city'           => 'Surat Wholesale Market',
            'payment_method' => 'UPI / Direct Request',
            'notes'          => 'Google Pay done',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('subscription_orders', [
            'user_id'        => $user->id,
            'customer_name'  => 'Ramesh Patel',
            'customer_phone' => '9825199999',
            'customer_city'  => 'Surat Wholesale Market',
            'status'         => 'pending',
            'amount'         => 49.00,
        ]);

        $user->refresh();
        $this->assertSame('9825199999', $user->phone);
    }

    public function test_admin_can_view_and_activate_subscription_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'role'                => 'user',
            'subscription_status' => 'expired',
            'subscription_ends_at'=> now()->subDay(),
            'trial_ends_at'       => now()->subDay(),
        ]);

        $order = SubscriptionOrder::create([
            'user_id'            => $user->id,
            'customer_name'      => $user->name,
            'customer_phone'     => '9876543210',
            'customer_email'     => $user->email,
            'customer_city'      => 'Ahmedabad',
            'amount'             => 49.00,
            'plan_duration_days' => 30,
            'payment_method'     => 'UPI',
            'status'             => 'pending',
        ]);

        // Check index page displays the order
        $viewResponse = $this->actingAs($admin)->get(route('admin.subscriptions.index'));
        $viewResponse->assertOk()
            ->assertSee('Customer Subscription Orders')
            ->assertSee('9876543210')
            ->assertSee('Ahmedabad');

        // Admin activates the order
        $activateResponse = $this->actingAs($admin)->post(route('admin.subscriptions.orders.activate', $order->id));
        $activateResponse->assertRedirect();

        $order->refresh();
        $this->assertSame('active', $order->status);
        $this->assertNotNull($order->activated_at);

        $user->refresh();
        $this->assertSame('active', $user->subscription_status);
        $this->assertTrue($user->hasActiveAccess());
        $this->assertTrue($user->subscription_ends_at->isFuture());
    }

    public function test_normal_user_cannot_activate_subscription_order(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $order = SubscriptionOrder::create([
            'user_id'            => $user->id,
            'customer_name'      => 'Test',
            'customer_phone'     => '9876543210',
            'customer_email'     => 'test@example.com',
            'amount'             => 49.00,
            'plan_duration_days' => 30,
            'status'             => 'pending',
        ]);

        // JSON request returns 403 Forbidden
        $jsonResponse = $this->actingAs($user)->postJson(route('admin.subscriptions.orders.activate', $order->id));
        $jsonResponse->assertForbidden();

        // Web request redirects to admin.login
        $webResponse = $this->actingAs($user)->post(route('admin.subscriptions.orders.activate', $order->id));
        $webResponse->assertRedirect(route('admin.login'));
    }
}
