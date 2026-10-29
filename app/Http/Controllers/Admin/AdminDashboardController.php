<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\CanonicalProduct;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\TelegramGroup;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Display the Admin Dashboard overview.
     */
    public function index(Request $request): View|JsonResponse
    {
        // ── 5 Core Required Metrics (Real Database Queries) ──────────────────
        $monitoredGroupsCount = TelegramGroup::where('is_active', true)->count();
        $totalGroupsCount     = TelegramGroup::count();
        $canonicalCount       = CanonicalProduct::count();
        $multiGroupCount      = CanonicalProduct::where('offers_count', '>', 1)->count();
        $totalOffersCount     = Product::count();
        $parsedPricesCount    = Product::whereNotNull('latest_price')->where('latest_price', '>', 0)->count();

        // ── Supplementary System Health Metrics ──────────────────────────────
        $totalUsers        = User::count();
        $activeSubscribers = User::where(function ($q) {
            $q->where('subscription_ends_at', '>', now())
              ->orWhere('trial_ends_at', '>', now());
        })->count();
        $blockedUsers      = User::where('status', 'blocked')->count();
        $failedJobsCount   = DB::table('failed_jobs')->count();
        $pendingJobsCount  = DB::table('jobs')->count();

        // ── Recent Activity / Feeds ──────────────────────────────────────────
        $recentProducts = Product::with(['group', 'canonicalProduct'])
            ->latest('last_updated_at')
            ->take(8)
            ->get();

        $recentPriceChanges = PriceHistory::with('product.group')
            ->latest('recorded_at')
            ->take(8)
            ->get();

        $recentLogs = AdminActivityLog::with('user')
            ->latest('id')
            ->take(6)
            ->get();

        $activeGroups = TelegramGroup::withCount('products')
            ->orderByDesc('last_synced_at')
            ->take(6)
            ->get();

        $data = [
            'stats' => [
                'monitored_groups'   => $monitoredGroupsCount,
                'total_groups'       => $totalGroupsCount,
                'canonical_products' => $canonicalCount,
                'multi_group_matches'=> $multiGroupCount,
                'total_offers'       => $totalOffersCount,
                'parsed_prices'      => $parsedPricesCount,
                'total_users'        => $totalUsers,
                'active_subscribers' => $activeSubscribers,
                'blocked_users'      => $blockedUsers,
                'failed_jobs'        => $failedJobsCount,
                'pending_jobs'       => $pendingJobsCount,
            ],
            'recentProducts'     => $recentProducts,
            'recentPriceChanges' => $recentPriceChanges,
            'recentLogs'         => $recentLogs,
            'activeGroups'       => $activeGroups,
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('admin.dashboard', $data);
    }
}
