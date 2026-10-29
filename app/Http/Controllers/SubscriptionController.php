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
    public function __construct(
        private readonly SettingService $settingService,
    ) {}

    /**
     * Submit subscription purchase request from User Dashboard.
     */
    public function purchase(Request $request): JsonResponse
    {
        $validated = $request->validate([
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

        $planPrice = (float) $this->settingService->get('subscription_price', 49.00);
        $durationDays = (int) $this->settingService->get('subscription_duration_days', 30);

        $order = SubscriptionOrder::create([
            'user_id'            => $user->id,
            'customer_name'      => $validated['name'],
            'customer_phone'     => $validated['phone'],
            'customer_email'     => $user->email,
            'customer_city'      => $validated['city'] ?? null,
            'amount'             => $planPrice,
            'plan_duration_days' => $durationDays,
            'payment_method'     => $validated['payment_method'] ?? 'UPI / Direct Request',
            'status'             => 'pending',
            'notes'              => $validated['notes'] ?? null,
        ]);

        AdminActivityLog::record(
            'subscription_order_placed',
            "Customer [{$order->customer_name}] submitted purchase request for ₹{$order->amount} (Phone: {$order->customer_phone})",
            $order,
            [
                'phone'  => $order->customer_phone,
                'email'  => $order->customer_email,
                'amount' => $order->amount,
                'city'   => $order->customer_city,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Thank you, {$order->customer_name}! Your subscription order has been placed. We will contact you at {$order->customer_phone} to verify and activate your 30-Day Pro Plan.",
            'order'   => $order,
        ]);
    }
}
