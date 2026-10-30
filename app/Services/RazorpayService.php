<?php

namespace App\Services;

use App\Models\SubscriptionOrder;
use App\Models\SubscriptionPayment;
use Exception;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class RazorpayService
{
    protected string $keyId;
    protected string $keySecret;
    protected string $webhookSecret;
    protected string $currency;
    protected ?Api $api = null;

    public function __construct()
    {
        $this->keyId = (string) config('services.razorpay.key_id', 'rzp_test_placeholder');
        $this->keySecret = (string) config('services.razorpay.key_secret', 'test_secret_placeholder');
        $this->webhookSecret = (string) config('services.razorpay.webhook_secret', 'test_webhook_secret_placeholder');
        $this->currency = (string) config('services.razorpay.currency', 'INR');

        if (!$this->isPlaceholder()) {
            $this->api = new Api($this->keyId, $this->keySecret);
        }
    }

    /**
     * Check whether we are operating with dummy/placeholder keys (e.g. before domain registration).
     */
    public function isPlaceholder(): bool
    {
        return empty($this->keyId)
            || empty($this->keySecret)
            || str_contains($this->keyId, 'placeholder')
            || str_contains($this->keySecret, 'placeholder');
    }

    /**
     * Get the public Key ID for Frontend Checkout.
     */
    public function getKeyId(): string
    {
        return $this->keyId;
    }

    /**
     * Get the default Currency.
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Create a Razorpay Order server-side for a local SubscriptionOrder.
     * Amount is calculated in integer minor units (paise: ₹1 = 100 paise).
     *
     * @throws Exception
     */
    public function createOrder(SubscriptionOrder $order): array
    {
        // 1. Calculate payable amount in minor units (paise) directly from database model
        $amountInPaise = (int) round($order->amount * 100);

        if ($amountInPaise <= 0) {
            throw new Exception("Invalid order amount for order ID: {$order->id}");
        }

        // 2. Prevent duplicate order creation if an active Razorpay order is already assigned
        if (!empty($order->razorpay_order_id)) {
            $existingPayment = SubscriptionPayment::where('razorpay_order_id', $order->razorpay_order_id)->first();
            if ($existingPayment && in_array($existingPayment->status, ['created', 'authorized'])) {
                return [
                    'id'       => $order->razorpay_order_id,
                    'amount'   => $amountInPaise,
                    'currency' => $this->currency,
                    'receipt'  => (string) $order->id,
                    'status'   => $existingPayment->status,
                ];
            }
        }

        // 3. Create order via official Razorpay SDK or safe mock when keys are placeholder
        if ($this->isPlaceholder() || app()->environment('testing')) {
            $razorpayOrderId = 'order_mock_' . bin2hex(random_bytes(8));
            $orderData = [
                'id'       => $razorpayOrderId,
                'amount'   => $amountInPaise,
                'currency' => $this->currency,
                'receipt'  => (string) $order->id,
                'status'   => 'created',
                'notes'    => [
                    'order_id'      => (string) $order->id,
                    'user_id'       => (string) $order->user_id,
                    'customer_name' => (string) $order->customer_name,
                ],
            ];
        } else {
            try {
                $razorpayOrder = $this->api->order->create([
                    'receipt'  => (string) $order->id,
                    'amount'   => $amountInPaise,
                    'currency' => $this->currency,
                    'notes'    => [
                        'order_id'      => (string) $order->id,
                        'user_id'       => (string) $order->user_id,
                        'customer_name' => (string) $order->customer_name,
                    ],
                ]);
                $orderData = $razorpayOrder->toArray();
                $razorpayOrderId = $orderData['id'];
            } catch (Exception $e) {
                Log::error('Razorpay Order Creation Failed: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                    'amount'   => $amountInPaise,
                ]);
                throw new Exception('Unable to create payment order with Razorpay: ' . $e->getMessage(), $e->getCode(), $e);
            }
        }

        // 4. Update the subscription order with the Razorpay order ID
        $order->update([
            'razorpay_order_id' => $razorpayOrderId,
            'payment_method'    => 'Razorpay',
        ]);

        // 5. Create payment tracking record
        SubscriptionPayment::create([
            'user_id'               => $order->user_id,
            'subscription_order_id' => $order->id,
            'razorpay_order_id'     => $razorpayOrderId,
            'amount'                => $order->amount,
            'currency'              => $this->currency,
            'status'                => 'created',
            'gateway_response'      => $this->sanitizePayload($orderData),
        ]);

        return $orderData;
    }

    /**
     * Verify payment signature from Razorpay Checkout.
     * Uses HMAC-SHA256 signature verification formula:
     * expectedSignature = hash_hmac('sha256', orderId . '|' . paymentId, secret)
     */
    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        if (empty($orderId) || empty($paymentId) || empty($signature)) {
            return false;
        }

        // Safe standard HMAC-SHA256 calculation
        $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->keySecret);

        if (hash_equals($expectedSignature, $signature)) {
            return true;
        }

        // If in placeholder/mock mode, support simulated test signatures
        if ($this->isPlaceholder() && str_starts_with($signature, 'mock_sig_')) {
            return true;
        }

        return false;
    }

    /**
     * Verify webhook signature from Razorpay Webhook header X-Razorpay-Signature.
     */
    public function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        if (empty($payload) || empty($signature)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);

        if (hash_equals($expectedSignature, $signature)) {
            return true;
        }

        // Placeholder support in local/test
        if ($this->isPlaceholder() && str_starts_with($signature, 'mock_webhook_sig_')) {
            return true;
        }

        return false;
    }

    /**
     * Fetch payment details from Razorpay to verify capture state.
     */
    public function fetchPayment(string $paymentId): ?array
    {
        if ($this->isPlaceholder() || app()->environment('testing')) {
            return [
                'id'       => $paymentId,
                'status'   => 'captured',
                'amount'   => null,
                'currency' => $this->currency,
                'method'   => 'upi',
            ];
        }

        try {
            $payment = $this->api->payment->fetch($paymentId);
            return $payment ? $payment->toArray() : null;
        } catch (Exception $e) {
            Log::warning('Razorpay fetch payment error: ' . $e->getMessage(), ['payment_id' => $paymentId]);
            return null;
        }
    }

    /**
     * Sanitize sensitive data from payload before database persistence.
     */
    public function sanitizePayload(array $payload): array
    {
        $sensitiveKeys = ['key_secret', 'secret', 'password', 'card[cvv]', 'cvv', 'card[number]'];

        foreach ($sensitiveKeys as $key) {
            if (isset($payload[$key])) {
                $payload[$key] = '***REDACTED***';
            }
        }

        return $payload;
    }
}

