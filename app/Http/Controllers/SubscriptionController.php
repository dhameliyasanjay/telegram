<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\SubscriptionOrder;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public const PLANS = [
        '1_month' => [
            'name'          => '1 Month',
            'duration_days' => 30,
            'amount'        => 49.00,
            'badge'         => 'Standard',
        ],
        '4_month' => [
            'name'          => '4 Months',
            'duration_days' => 120,
            'amount'        => 179.00,
            'badge'         => 'Save 9%',
        ],
        '6_month' => [
            'name'          => '6 Months',
            'duration_days' => 180,
            'amount'        => 249.00,
            'badge'         => 'Most Popular',
        ],
        '1_year' => [
            'name'          => '1 Year',
            'duration_days' => 365,
            'amount'        => 449.00,
            'badge'         => 'Best Value',
        ],
    ];

    public function __construct(
        private readonly SettingService $settingService,
    ) {}

    /**
     * Submit subscription purchase request from User Dashboard.
     */
    public function purchase(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_key'       => ['nullable', 'string', 'in:1_month,4_month,6_month,1_year'],
            'name'           => ['required', 'string', 'max:255'],
            'phone'          => ['required', 'string', 'min:8', 'max:25'],
            'city'           => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Save phone to user profile if not set
        if (empty($user->phone)) {
            $user->phone = $validated['phone'];
            $user->save();
        }

        $planKey = $validated['plan_key'] ?? '1_month';
        $plans = $this->settingService->getSubscriptionPlans();
        $plan = $plans[$planKey] ?? $plans['1_month'];

        $planPrice = (float) $plan['amount'];
        $durationDays = (int) $plan['duration_days'];
        $planName = $plan['name'];

        $orderNotes = "Plan: {$planName} ({$durationDays} Days)";
        if (!empty($validated['notes'])) {
            $orderNotes .= " | " . $validated['notes'];
        }

        $order = SubscriptionOrder::create([
            'user_id'            => $user->id,
            'customer_name'      => $validated['name'],
            'customer_phone'     => $validated['phone'],
            'customer_email'     => $user->email,
            'customer_city'      => $validated['city'] ?? null,
            'amount'             => $planPrice,
            'plan_duration_days' => $durationDays,
            'payment_method'     => $validated['payment_method'] ?? 'Razorpay (Pending Integration) / Direct Request',
            'status'             => 'pending',
            'notes'              => $orderNotes,
        ]);

        AdminActivityLog::record(
            'subscription_order_placed',
            "Customer [{$order->customer_name}] submitted purchase request for {$planName} — ₹{$order->amount} (Phone: {$order->customer_phone})",
            $order,
            [
                'plan'   => $planName,
                'phone'  => $order->customer_phone,
                'email'  => $order->customer_email,
                'amount' => $order->amount,
                'days'   => $order->plan_duration_days,
                'city'   => $order->customer_city,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Thank you, {$order->customer_name}! Your subscription request for {$planName} (₹{$order->amount}) has been placed. We will verify and activate your plan.",
            'order'   => $order,
            'plan'    => [
                'key'    => $planKey,
                'name'   => $planName,
                'days'   => $durationDays,
                'amount' => $planPrice,
            ],
        ]);
    }
}

