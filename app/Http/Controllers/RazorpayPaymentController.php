<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\SubscriptionOrder;
use App\Models\SubscriptionPayment;
use App\Services\RazorpayService;
use App\Services\SettingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RazorpayPaymentController extends Controller
{
    public function __construct(
        protected RazorpayService $razorpayService,
        protected SettingService $settingService,
    ) {}

    /**
     * Create Razorpay order for authenticated user's selected subscription plan.
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id'       => ['nullable', 'integer', 'exists:subscription_orders,id'],
            'plan_key'       => ['nullable', 'string', 'in:1_month,4_month,6_month,1_year'],
            'name'           => ['nullable', 'string', 'max:255'],
            'phone'          => ['nullable', 'string', 'min:8', 'max:25'],
            'city'           => ['nullable', 'string', 'max:100'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. Either use existing order or create new order from server-configured plan
        if (!empty($validated['order_id'])) {
            $order = SubscriptionOrder::where('id', $validated['order_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();
        } else {
            $planKey = $validated['plan_key'] ?? '1_month';
            $plans = $this->settingService->getSubscriptionPlans();
            $plan = $plans[$planKey] ?? $plans['1_month'];

            $planPrice = (float) $plan['amount'];
            $durationDays = (int) $plan['duration_days'];
            $planName = $plan['name'];

            $name = !empty($validated['name']) ? $validated['name'] : $user->name;
            $phone = !empty($validated['phone']) ? $validated['phone'] : ($user->phone ?? '9876543210');

            if (empty($user->phone) && !empty($validated['phone'])) {
                $user->phone = $validated['phone'];
                $user->save();
            }

            $orderNotes = "Plan: {$planName} ({$durationDays} Days)";
            if (!empty($validated['notes'])) {
                $orderNotes .= " | " . $validated['notes'];
            }

            $order = SubscriptionOrder::create([
                'user_id'            => $user->id,
                'customer_name'      => $name,
                'customer_phone'     => $phone,
                'customer_email'     => $user->email,
                'customer_city'      => $validated['city'] ?? null,
                'amount'             => $planPrice,
                'plan_duration_days' => $durationDays,
                'payment_method'     => 'Razorpay',
                'status'             => 'pending',
                'notes'              => $orderNotes,
            ]);
        }

        // 2. Reject if order is already active / paid
        if ($order->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This subscription order is already paid and active.',
            ], 400);
        }

        // 3. Create order on Razorpay
        try {
            $razorpayOrder = $this->razorpayService->createOrder($order);

            return response()->json([
                'success'           => true,
                'razorpay_order_id' => $razorpayOrder['id'],
                'key_id'            => $this->razorpayService->getKeyId(),
                'amount'            => $razorpayOrder['amount'], // in paise
                'currency'          => $razorpayOrder['currency'],
                'order_id'          => $order->id,
                'plan_name'         => "Plan ({$order->plan_duration_days} Days)",
                'duration_days'     => $order->plan_duration_days,
                'customer'          => [
                    'name'  => $order->customer_name,
                    'email' => $order->customer_email,
                    'phone' => $order->customer_phone,
                ],
                'is_placeholder'    => $this->razorpayService->isPlaceholder(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Server-side payment verification after checkout completion.
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'razorpay_order_id'   => ['required', 'string', 'max:255'],
            'razorpay_payment_id' => ['required', 'string', 'max:255'],
            'razorpay_signature'  => ['required', 'string', 'max:255'],
            'order_id'            => ['nullable', 'integer', 'exists:subscription_orders,id'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. Locate local order by razorpay_order_id or ID
        $query = SubscriptionOrder::where('razorpay_order_id', $validated['razorpay_order_id']);
        if (!empty($validated['order_id'])) {
            $query->orWhere('id', $validated['order_id']);
        }
        $order = $query->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription order not found for the provided Razorpay order ID.',
            ], 404);
        }

        // 2. Authorization check: order must belong to authenticated user
        if ((int) $order->user_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this order.',
            ], 403);
        }

        // 3. Idempotency: If already active, return success immediately
        if ($order->status === 'active') {
            return response()->json([
                'success'        => true,
                'already_active' => true,
                'message'        => 'Payment already verified and subscription is active.',
                'order'          => $order,
            ]);
        }

        // 4. Verify HMAC-SHA256 signature
        $isValid = $this->razorpayService->verifyPaymentSignature(
            $validated['razorpay_order_id'],
            $validated['razorpay_payment_id'],
            $validated['razorpay_signature']
        );

        if (!$isValid) {
            // Log and save failure record
            SubscriptionPayment::create([
                'user_id'               => $user->id,
                'subscription_order_id' => $order->id,
                'razorpay_order_id'     => $validated['razorpay_order_id'],
                'razorpay_payment_id'   => $validated['razorpay_payment_id'],
                'razorpay_signature'    => $validated['razorpay_signature'],
                'amount'                => $order->amount,
                'status'                => 'failed',
                'error_code'            => 'INVALID_SIGNATURE',
                'error_description'     => 'HMAC-SHA256 signature mismatch detected.',
            ]);

            Log::warning('Razorpay payment signature mismatch', [
                'user_id'             => $user->id,
                'order_id'            => $order->id,
                'razorpay_order_id'   => $validated['razorpay_order_id'],
                'razorpay_payment_id' => $validated['razorpay_payment_id'],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed: Invalid signature.',
            ], 422);
        }

        // 5. Verify payment capture status from Razorpay API
        $paymentDetails = $this->razorpayService->fetchPayment($validated['razorpay_payment_id']);
        if ($paymentDetails && isset($paymentDetails['status']) && $paymentDetails['status'] === 'failed') {
            return response()->json([
                'success' => false,
                'message' => 'Payment failed on gateway.',
            ], 422);
        }

        // 6. Complete payment in a database transaction
        DB::transaction(function () use ($order, $validated, $paymentDetails, $user) {
            $order->update([
                'status'              => 'active',
                'razorpay_order_id'   => $validated['razorpay_order_id'],
                'razorpay_payment_id' => $validated['razorpay_payment_id'],
                'razorpay_signature'  => $validated['razorpay_signature'],
                'payment_method'      => 'Razorpay' . (!empty($paymentDetails['method']) ? " ({$paymentDetails['method']})" : ''),
                'paid_at'             => now(),
                'activated_at'        => now(),
            ]);

            SubscriptionPayment::updateOrCreate(
                ['razorpay_order_id' => $validated['razorpay_order_id']],
                [
                    'user_id'               => $user->id,
                    'subscription_order_id' => $order->id,
                    'razorpay_payment_id'   => $validated['razorpay_payment_id'],
                    'razorpay_signature'    => $validated['razorpay_signature'],
                    'amount'                => $order->amount,
                    'currency'              => $this->razorpayService->getCurrency(),
                    'status'                => 'captured',
                    'payment_method'        => $paymentDetails['method'] ?? 'online',
                    'gateway_response'      => $this->razorpayService->sanitizePayload($paymentDetails ?? []),
                    'paid_at'               => now(),
                ]
            );

            // Grant subscription days directly to user
            $user->grantSubscription($order->plan_duration_days);

            AdminActivityLog::record(
                'subscription_payment_verified',
                "User [{$user->name}] paid ₹{$order->amount} via Razorpay ({$validated['razorpay_payment_id']}) — {$order->plan_duration_days} days granted",
                $order,
                [
                    'payment_id'          => $validated['razorpay_payment_id'],
                    'order_id'            => $validated['razorpay_order_id'],
                    'amount'              => $order->amount,
                    'days'                => $order->plan_duration_days,
                    'subscription_status' => $user->subscription_status,
                ]
            );
        });

        $user->refresh();

        return response()->json([
            'success'              => true,
            'message'              => 'Payment successfully verified and subscription activated!',
            'order'                => $order->fresh(),
            'subscription_ends_at' => $user->subscription_ends_at ? $user->subscription_ends_at->toFormattedDateString() : null,
            'subscription_status'  => $user->subscription_status,
        ]);
    }

    /**
     * Webhook endpoint for asynchronous gateway notifications.
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $signature = $request->header('X-Razorpay-Signature');
        $rawPayload = $request->getContent();

        // 1. Verify webhook signature
        if (!$this->razorpayService->verifyWebhookSignature($rawPayload, $signature)) {
            Log::warning('Razorpay Webhook: Invalid signature', [
                'signature' => $signature,
                'ip'        => $request->ip(),
            ]);
            return response()->json(['error' => 'Invalid webhook signature'], 400);
        }

        $data = json_decode($rawPayload, true);
        if (!$data || !isset($data['event'])) {
            return response()->json(['error' => 'Malformed payload'], 400);
        }

        $event = $data['event'];
        Log::info("Razorpay Webhook Received: {$event}");

        // 2. Handle relevant events idempotently
        if (in_array($event, ['payment.captured', 'order.paid'])) {
            $paymentEntity = $data['payload']['payment']['entity'] ?? [];
            $razorpayOrderId = $paymentEntity['order_id'] ?? ($data['payload']['order']['entity']['id'] ?? null);
            $razorpayPaymentId = $paymentEntity['id'] ?? null;

            if ($razorpayOrderId) {
                $order = SubscriptionOrder::where('razorpay_order_id', $razorpayOrderId)->first();

                if ($order && $order->status !== 'active') {
                    DB::transaction(function () use ($order, $paymentEntity, $razorpayPaymentId, $razorpayOrderId, $event) {
                        $order->update([
                            'status'              => 'active',
                            'razorpay_payment_id' => $razorpayPaymentId,
                            'paid_at'             => now(),
                            'activated_at'        => now(),
                        ]);

                        if ($order->user) {
                            $order->user->grantSubscription($order->plan_duration_days);
                        }

                        SubscriptionPayment::updateOrCreate(
                            ['razorpay_order_id' => $razorpayOrderId],
                            [
                                'user_id'               => $order->user_id,
                                'subscription_order_id' => $order->id,
                                'razorpay_payment_id'   => $razorpayPaymentId,
                                'amount'                => $order->amount,
                                'currency'              => $this->razorpayService->getCurrency(),
                                'status'                => 'captured',
                                'payment_method'        => $paymentEntity['method'] ?? 'webhook',
                                'gateway_response'      => $this->razorpayService->sanitizePayload($paymentEntity),
                                'paid_at'               => now(),
                            ]
                        );

                        AdminActivityLog::record(
                            'subscription_webhook_activated',
                            "Subscription Order #{$order->id} auto-activated via Razorpay webhook ({$event})",
                            $order,
                            ['event' => $event, 'payment_id' => $razorpayPaymentId]
                        );
                    });
                }
            }
        } elseif ($event === 'payment.failed') {
            $paymentEntity = $data['payload']['payment']['entity'] ?? [];
            $razorpayOrderId = $paymentEntity['order_id'] ?? null;
            $razorpayPaymentId = $paymentEntity['id'] ?? null;

            if ($razorpayOrderId) {
                $order = SubscriptionOrder::where('razorpay_order_id', $razorpayOrderId)->first();
                if ($order) {
                    SubscriptionPayment::create([
                        'user_id'               => $order->user_id,
                        'subscription_order_id' => $order->id,
                        'razorpay_order_id'     => $razorpayOrderId,
                        'razorpay_payment_id'   => $razorpayPaymentId,
                        'amount'                => $order->amount,
                        'currency'              => $this->razorpayService->getCurrency(),
                        'status'                => 'failed',
                        'error_code'            => $paymentEntity['error_code'] ?? 'PAYMENT_FAILED',
                        'error_description'     => $paymentEntity['error_description'] ?? 'Payment failed via webhook',
                        'gateway_response'      => $this->razorpayService->sanitizePayload($paymentEntity),
                    ]);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Get real-time status of a subscription order.
     */
    public function getStatus(SubscriptionOrder $order): JsonResponse
    {
        if ((int) $order->user_id !== (int) Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success'           => true,
            'order_id'          => $order->id,
            'status'            => $order->status,
            'razorpay_order_id' => $order->razorpay_order_id,
            'paid_at'           => $order->paid_at ? $order->paid_at->toIso8601String() : null,
            'amount'            => $order->amount,
            'plan_duration_days'=> $order->plan_duration_days,
        ]);
    }
}
