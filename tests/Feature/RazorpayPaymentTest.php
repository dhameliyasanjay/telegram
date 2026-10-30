<?php

namespace Tests\Feature;

use App\Models\SubscriptionOrder;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\RazorpayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class RazorpayPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.razorpay.key_id', 'rzp_test_testkey12345');
        Config::set('services.razorpay.key_secret', 'testsecret987654321');
        Config::set('services.razorpay.webhook_secret', 'webhooksecret123456');
    }

    /**
     * Scenario 1: Successful payment verification and subscription activation.
     */
    public function test_scenario_1_successful_payment(): void
    {
        $user = User::factory()->create([
            'role'                 => 'user',
            'subscription_ends_at' => null,
            'subscription_status'  => 'trial',
        ]);

        // 1. Authenticated user creates Razorpay order for 6 months plan (₹249 / 180 days)
        $orderResponse = $this->actingAs($user)->postJson(route('subscription.razorpay.order'), [
            'plan_key' => '6_month',
            'name'     => 'Sanjay Test',
            'phone'    => '9825100000',
        ]);

        $orderResponse->assertOk()
            ->assertJson([
                'success'       => true,
                'amount'        => 24900, // 249 * 100 paise
                'currency'      => 'INR',
                'duration_days' => 180,
            ]);

        $orderData = $orderResponse->json();
        $razorpayOrderId = $orderData['razorpay_order_id'];
        $orderId = $orderData['order_id'];
        $razorpayPaymentId = 'pay_test_' . bin2hex(random_bytes(6));

        // Generate valid HMAC-SHA256 signature
        $validSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, 'testsecret987654321');

        // 2. Client submits payment verification
        $verifyResponse = $this->actingAs($user)->postJson(route('subscription.razorpay.verify'), [
            'razorpay_order_id'   => $razorpayOrderId,
            'razorpay_payment_id' => $razorpayPaymentId,
            'razorpay_signature'  => $validSignature,
            'order_id'            => $orderId,
        ]);

        $verifyResponse->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        // 3. Database assertions
        $this->assertDatabaseHas('subscription_orders', [
            'id'                  => $orderId,
            'status'              => 'active',
            'razorpay_order_id'   => $razorpayOrderId,
            'razorpay_payment_id' => $razorpayPaymentId,
        ]);

        $this->assertDatabaseHas('subscription_payments', [
            'subscription_order_id' => $orderId,
            'razorpay_order_id'     => $razorpayOrderId,
            'razorpay_payment_id'   => $razorpayPaymentId,
            'status'                => 'captured',
            'amount'                => 249.00,
        ]);

        $user->refresh();
        $this->assertSame('active', $user->subscription_status);
        $this->assertNotNull($user->subscription_ends_at);
        $this->assertTrue($user->subscription_ends_at->isFuture());
    }

    /**
     * Scenario 2: Failed payment gateway response marks transaction as failed.
     */
    public function test_scenario_2_failed_payment_recorded(): void
    {
        $user = User::factory()->create();

        $order = SubscriptionOrder::create([
            'user_id'            => $user->id,
            'customer_name'      => $user->name,
            'customer_phone'     => '9825100000',
            'customer_email'     => $user->email,
            'amount'             => 49.00,
            'plan_duration_days' => 30,
            'razorpay_order_id'  => 'order_test_fail123',
            'status'             => 'pending',
        ]);

        // Create mock of RazorpayService where fetchPayment returns status 'failed'
        $mockService = $this->mock(RazorpayService::class, function ($mock) {
            $mock->shouldReceive('verifyPaymentSignature')->andReturn(true);
            $mock->shouldReceive('fetchPayment')->andReturn([
                'id'     => 'pay_test_failed123',
                'status' => 'failed',
                'method' => 'card',
            ]);
        });

        $response = $this->actingAs($user)->postJson(route('subscription.razorpay.verify'), [
            'razorpay_order_id'   => 'order_test_fail123',
            'razorpay_payment_id' => 'pay_test_failed123',
            'razorpay_signature'  => 'any_valid_sig',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Payment failed on gateway.',
            ]);

        $order->refresh();
        $this->assertSame('pending', $order->status);
    }

    /**
     * Scenario 3: Invalid signature is rejected with 422 and logged.
     */
    public function test_scenario_3_invalid_signature_is_rejected(): void
    {
        $user = User::factory()->create();

        $order = SubscriptionOrder::create([
            'user_id'            => $user->id,
            'customer_name'      => $user->name,
            'customer_phone'     => '9825100000',
            'customer_email'     => $user->email,
            'amount'             => 49.00,
            'plan_duration_days' => 30,
            'razorpay_order_id'  => 'order_test_invalidsig',
            'status'             => 'pending',
        ]);

        $response = $this->actingAs($user)->postJson(route('subscription.razorpay.verify'), [
            'razorpay_order_id'   => 'order_test_invalidsig',
            'razorpay_payment_id' => 'pay_test_fake',
            'razorpay_signature'  => 'tampered_forged_signature_12345',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Payment verification failed: Invalid signature.',
            ]);

        $this->assertDatabaseHas('subscription_payments', [
            'subscription_order_id' => $order->id,
            'status'                => 'failed',
            'error_code'            => 'INVALID_SIGNATURE',
        ]);

        $order->refresh();
        $this->assertSame('pending', $order->status);
    }

    /**
     * Scenario 4: Incorrect / non-existent order ID returns 404.
     */
    public function test_scenario_4_incorrect_order_id_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('subscription.razorpay.verify'), [
            'razorpay_order_id'   => 'order_non_existent_99999',
            'razorpay_payment_id' => 'pay_random_123',
            'razorpay_signature'  => 'sig_123',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Subscription order not found for the provided Razorpay order ID.',
            ]);
    }

    /**
     * Scenario 5: Amount mismatch prevention - server calculates amount strictly from server configuration.
     */
    public function test_scenario_5_amount_mismatch_prevention(): void
    {
        $user = User::factory()->create();

        // Client attempts to pass a tampered amount or fake price
        $response = $this->actingAs($user)->postJson(route('subscription.razorpay.order'), [
            'plan_key' => '1_year',
            'amount'   => 1.00, // Client tries to pay ₹1 instead of ₹449
        ]);

        $response->assertOk();
        $data = $response->json();

        // Server strictly charged the official 1_year price: ₹449 = 44900 paise
        $this->assertSame(44900, $data['amount']);
        $this->assertDatabaseHas('subscription_orders', [
            'id'     => $data['order_id'],
            'amount' => 449.00,
        ]);
    }

    /**
     * Scenario 6: Unauthenticated request is blocked.
     */
    public function test_scenario_6_unauthenticated_request_is_blocked(): void
    {
        $response = $this->postJson(route('subscription.razorpay.order'), [
            'plan_key' => '1_month',
        ]);

        $response->assertStatus(302)->assertRedirect(route('login'));
    }

    /**
     * Scenario 7: User cannot verify another user's order (403 Forbidden).
     */
    public function test_scenario_7_cannot_verify_another_users_order(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();

        $order = SubscriptionOrder::create([
            'user_id'            => $victim->id,
            'customer_name'      => $victim->name,
            'customer_phone'     => '9825100000',
            'customer_email'     => $victim->email,
            'amount'             => 49.00,
            'plan_duration_days' => 30,
            'razorpay_order_id'  => 'order_victim_123',
            'status'             => 'pending',
        ]);

        $response = $this->actingAs($attacker)->postJson(route('subscription.razorpay.verify'), [
            'razorpay_order_id'   => 'order_victim_123',
            'razorpay_payment_id' => 'pay_123',
            'razorpay_signature'  => 'sig_123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized access to this order.',
            ]);
    }

    /**
     * Scenario 8: Duplicate verification request is idempotent.
     */
    public function test_scenario_8_duplicate_verification_request_is_idempotent(): void
    {
        $user = User::factory()->create(['subscription_ends_at' => null]);

        $order = SubscriptionOrder::create([
            'user_id'             => $user->id,
            'customer_name'       => $user->name,
            'customer_phone'      => '9825100000',
            'customer_email'      => $user->email,
            'amount'              => 49.00,
            'plan_duration_days'  => 30,
            'razorpay_order_id'   => 'order_idem_123',
            'razorpay_payment_id' => 'pay_idem_123',
            'status'              => 'active',
            'paid_at'             => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('subscription.razorpay.verify'), [
            'razorpay_order_id'   => 'order_idem_123',
            'razorpay_payment_id' => 'pay_idem_123',
            'razorpay_signature'  => 'any_signature',
        ]);

        $response->assertOk()
            ->assertJson([
                'success'        => true,
                'already_active' => true,
            ]);
    }

    /**
     * Scenario 9: Duplicate webhook delivery is handled idempotently.
     */
    public function test_scenario_9_duplicate_webhook_delivery_is_idempotent(): void
    {
        $user = User::factory()->create(['subscription_ends_at' => null]);

        $order = SubscriptionOrder::create([
            'user_id'            => $user->id,
            'customer_name'      => $user->name,
            'customer_phone'     => '9825100000',
            'customer_email'     => $user->email,
            'amount'             => 49.00,
            'plan_duration_days' => 30,
            'razorpay_order_id'  => 'order_webhook_idem_123',
            'status'             => 'pending',
        ]);

        $payload = json_encode([
            'event'   => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id'       => 'pay_webhook_999',
                        'order_id' => 'order_webhook_idem_123',
                        'amount'   => 4900,
                        'currency' => 'INR',
                        'method'   => 'upi',
                    ],
                ],
            ],
        ]);

        $signature = hash_hmac('sha256', $payload, 'webhooksecret123456');

        // First delivery
        $res1 = $this->call('POST', route('webhooks.razorpay'), [], [], [], [
            'HTTP_X-Razorpay-Signature' => $signature,
            'CONTENT_TYPE'              => 'application/json',
        ], $payload);

        $res1->assertOk();
        $order->refresh();
        $user->refresh();
        $this->assertSame('active', $order->status);
        $firstExpiry = $user->subscription_ends_at;

        // Second delivery (duplicate retry from Razorpay)
        $res2 = $this->call('POST', route('webhooks.razorpay'), [], [], [], [
            'HTTP_X-Razorpay-Signature' => $signature,
            'CONTENT_TYPE'              => 'application/json',
        ], $payload);

        $res2->assertOk();
        $user->refresh();

        // Access was not double-granted
        $this->assertEquals($firstExpiry->toIso8601String(), $user->subscription_ends_at->toIso8601String());
    }

    /**
     * Scenario 10: Network timeout and pending payment status check.
     */
    public function test_scenario_10_pending_payment_status_check(): void
    {
        $user = User::factory()->create();

        $order = SubscriptionOrder::create([
            'user_id'            => $user->id,
            'customer_name'      => $user->name,
            'customer_phone'     => '9825100000',
            'customer_email'     => $user->email,
            'amount'             => 49.00,
            'plan_duration_days' => 30,
            'razorpay_order_id'  => 'order_pending_123',
            'status'             => 'pending',
        ]);

        $response = $this->actingAs($user)->getJson(route('subscription.orders.status', $order));

        $response->assertOk()
            ->assertJson([
                'success'           => true,
                'status'            => 'pending',
                'razorpay_order_id' => 'order_pending_123',
            ]);
    }

    /**
     * Scenario 11: Failed payment retry flow on the same order.
     */
    public function test_scenario_11_failed_payment_retry_flow(): void
    {
        $user = User::factory()->create(['subscription_ends_at' => null]);

        $order = SubscriptionOrder::create([
            'user_id'            => $user->id,
            'customer_name'      => $user->name,
            'customer_phone'     => '9825100000',
            'customer_email'     => $user->email,
            'amount'             => 49.00,
            'plan_duration_days' => 30,
            'razorpay_order_id'  => 'order_retry_123',
            'status'             => 'pending',
        ]);

        // Attempt 1: Failed signature
        $failRes = $this->actingAs($user)->postJson(route('subscription.razorpay.verify'), [
            'razorpay_order_id'   => 'order_retry_123',
            'razorpay_payment_id' => 'pay_retry_attempt1',
            'razorpay_signature'  => 'bad_sig',
        ]);
        $failRes->assertStatus(422);

        $order->refresh();
        $this->assertSame('pending', $order->status);

        // Attempt 2: User clicks Retry Payment with valid signature
        $payId2 = 'pay_retry_attempt2';
        $validSig = hash_hmac('sha256', 'order_retry_123|' . $payId2, 'testsecret987654321');

        $successRes = $this->actingAs($user)->postJson(route('subscription.razorpay.verify'), [
            'razorpay_order_id'   => 'order_retry_123',
            'razorpay_payment_id' => $payId2,
            'razorpay_signature'  => $validSig,
        ]);
        $successRes->assertOk();

        $order->refresh();
        $this->assertSame('active', $order->status);
        $this->assertSame($payId2, $order->razorpay_payment_id);
    }

    /**
     * Scenario 12: Payment status reconciliation via verified Razorpay webhook.
     */
    public function test_scenario_12_webhook_reconciles_pending_order(): void
    {
        $user = User::factory()->create([
            'subscription_ends_at' => null,
            'subscription_status'  => 'trial',
        ]);

        $order = SubscriptionOrder::create([
            'user_id'            => $user->id,
            'customer_name'      => $user->name,
            'customer_phone'     => '9825100000',
            'customer_email'     => $user->email,
            'amount'             => 179.00,
            'plan_duration_days' => 120,
            'razorpay_order_id'  => 'order_reconcile_456',
            'status'             => 'pending',
        ]);

        $payload = json_encode([
            'event'   => 'order.paid',
            'payload' => [
                'order' => [
                    'entity' => [
                        'id'     => 'order_reconcile_456',
                        'amount' => 17900,
                        'status' => 'paid',
                    ],
                ],
                'payment' => [
                    'entity' => [
                        'id'       => 'pay_reconcile_789',
                        'order_id' => 'order_reconcile_456',
                        'amount'   => 17900,
                        'currency' => 'INR',
                        'method'   => 'netbanking',
                    ],
                ],
            ],
        ]);

        $signature = hash_hmac('sha256', $payload, 'webhooksecret123456');

        $response = $this->call('POST', route('webhooks.razorpay'), [], [], [], [
            'HTTP_X-Razorpay-Signature' => $signature,
            'CONTENT_TYPE'              => 'application/json',
        ], $payload);

        $response->assertOk()
            ->assertJson(['status' => 'ok']);

        $order->refresh();
        $user->refresh();

        $this->assertSame('active', $order->status);
        $this->assertSame('pay_reconcile_789', $order->razorpay_payment_id);
        $this->assertSame('active', $user->subscription_status);
        $this->assertNotNull($user->subscription_ends_at);
        $this->assertTrue($user->subscription_ends_at->isFuture());
    }
}
