<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSubscriptionController extends Controller
{
    public function __construct(
        private readonly SettingService $settingService,
    ) {}

    /**
     * Show subscriptions dashboard and user subscription statuses.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->settingService->seedDefaultsIfEmpty();

        // Plan configurations
        $trialDays       = (int) $this->settingService->get('trial_days', 30);
        $planPrice       = (float) $this->settingService->get('subscription_price', 49.00);
        $planDuration    = (int) $this->settingService->get('subscription_duration_days', 30);
        $currencySymbol  = (string) $this->settingService->get('currency_symbol', '₹');

        // Subscriptions metrics
        $activePaidCount = User::where('subscription_status', 'active')
            ->where('subscription_ends_at', '>', now())
            ->count();

        $activeTrialCount = User::where('subscription_status', 'trial')
            ->where('trial_ends_at', '>', now())
            ->count();

        $expiredCount = User::where(function ($q) {
            $q->where('subscription_status', 'expired')
              ->orWhere(function ($sub) {
                  $sub->where('subscription_ends_at', '<=', now())
                      ->where('trial_ends_at', '<=', now());
              });
        })->where('role', '!=', 'admin')->count();

        // Query users with status filters
        $query = User::query();

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'paid') {
                $query->where('subscription_status', 'active')->where('subscription_ends_at', '>', now());
            } elseif ($status === 'trial') {
                $query->where('subscription_status', 'trial')->where('trial_ends_at', '>', now());
            } elseif ($status === 'expired') {
                $query->where('role', '!=', 'admin')
                      ->where(function ($sub) {
                          $sub->where('subscription_status', 'expired')
                              ->orWhere('subscription_ends_at', '<=', now());
                      });
            }
        }

        if ($request->filled('search')) {
            $term = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', $term)->orWhere('email', 'LIKE', $term);
            });
        }

        $subscribers = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $recentOrders = \App\Models\SubscriptionOrder::with('user')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        $data = [
            'trialDays'        => $trialDays,
            'planPrice'        => $planPrice,
            'planDuration'     => $planDuration,
            'currencySymbol'   => $currencySymbol,
            'activePaidCount'  => $activePaidCount,
            'activeTrialCount' => $activeTrialCount,
            'expiredCount'     => $expiredCount,
            'subscribers'      => $subscribers,
            'recentOrders'     => $recentOrders,
            'filters'          => $request->only(['status', 'search']),
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('admin.subscriptions.index', $data);
    }

    /**
     * Activate customer subscription order directly from Admin Panel.
     */
    public function activateOrder(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $order = \App\Models\SubscriptionOrder::findOrFail($id);

        if ($order->user) {
            $order->user->grantSubscription($order->plan_duration_days);
        }

        $order->status = 'active';
        $order->activated_at = now();
        $order->save();

        AdminActivityLog::record(
            'subscription_order_activated',
            "Activated Pro plan for order #{$order->id} - Customer [{$order->customer_name}] ({$order->customer_phone})",
            $order,
            [
                'phone'   => $order->customer_phone,
                'email'   => $order->customer_email,
                'amount'  => $order->amount,
            ]
        );

        $msg = "Subscription activated for {$order->customer_name} ({$order->customer_phone}) for {$order->plan_duration_days} days!";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Update default subscription plan pricing & trial duration.
     */
    public function updatePlan(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'trial_days'                 => ['required', 'integer', 'min:0', 'max:365'],
            'subscription_price'         => ['required', 'numeric', 'min:0'],
            'subscription_duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'currency_symbol'            => ['required', 'string', 'max:5'],
        ]);

        $this->settingService->set('trial_days', $validated['trial_days'], 'integer', 'subscription', 'Free Trial Duration (Days)');
        $this->settingService->set('subscription_price', $validated['subscription_price'], 'float', 'subscription', 'Subscription Plan Price');
        $this->settingService->set('subscription_duration_days', $validated['subscription_duration_days'], 'integer', 'subscription', 'Subscription Duration (Days)');
        $this->settingService->set('currency_symbol', $validated['currency_symbol'], 'string', 'subscription', 'Currency Symbol');

        AdminActivityLog::record(
            'subscription_plan_updated',
            "Updated plan: Price {$validated['currency_symbol']}{$validated['subscription_price']} for {$validated['subscription_duration_days']} days, Free Trial: {$validated['trial_days']} days",
            null,
            $validated
        );

        $msg = "Subscription plan configuration updated successfully.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }
}

