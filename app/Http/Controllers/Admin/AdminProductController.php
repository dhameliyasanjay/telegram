<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\CanonicalProduct;
use App\Models\Product;
use App\Models\TelegramGroup;
use App\Services\ProductIdentityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminProductController extends Controller
{
    public function __construct(
        private readonly ProductIdentityService $identityService,
    ) {}

    /**
     * List canonical products with rich filtering.
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = CanonicalProduct::with(['cheapestGroup', 'offers.group']);

        // Search term
        if ($request->filled('search')) {
            $term = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', $term)
                  ->orWhere('brand', 'LIKE', $term)
                  ->orWhere('model', 'LIKE', $term)
                  ->orWhere('category', 'LIKE', $term)
                  ->orWhere('canonical_key', 'LIKE', $term);
            });
        }

        // Filter by group
        if ($request->filled('group_id')) {
            $groupId = (int) $request->input('group_id');
            $query->whereHas('offers', fn($q) => $q->where('group_id', $groupId));
        }

        // Multi-group filter
        if ($request->boolean('multi_group_only')) {
            $query->where('offers_count', '>', 1);
        }

        // Suspicious / Price Anomaly filter
        if ($request->filled('filter_issue')) {
            $issue = $request->input('filter_issue');
            if ($issue === 'zero_price') {
                $query->where(function ($q) {
                    $q->whereNull('cheapest_price')
                      ->orWhere('cheapest_price', '<=', 0);
                });
            } elseif ($issue === 'high_spread') {
                // Where difference between highest and cheapest is significant
                $query->whereRaw('(highest_price - cheapest_price) > (cheapest_price * 0.5)')
                      ->where('offers_count', '>', 1);
            }
        }

        // Sorting
        $sort = $request->input('sort', 'last_observed_at');
        $dir  = $request->input('dir', 'desc');

        match ($sort) {
            'cheapest_price' => $query->orderBy('cheapest_price', $dir === 'asc' ? 'asc' : 'desc'),
            'offers_count'   => $query->orderBy('offers_count', $dir === 'asc' ? 'asc' : 'desc'),
            'name'           => $query->orderBy('name', $dir === 'asc' ? 'asc' : 'desc'),
            default          => $query->orderByDesc('last_observed_at'),
        };

        $products = $query->paginate(24)->withQueryString();
        $groups = TelegramGroup::orderBy('title')->get(['id', 'title']);

        if ($request->wantsJson()) {
            return response()->json($products);
        }

        return view('admin.products.index', [
            'products' => $products,
            'groups'   => $groups,
            'filters'  => $request->all(),
        ]);
    }

    /**
     * Inspect a canonical product and all its individual group offers & price history.
     */
    public function show(Request $request, int $id): View|JsonResponse
    {
        $canonical = CanonicalProduct::with([
            'cheapestGroup',
            'offers.group',
            'offers.priceHistory' => fn($q) => $q->orderByDesc('recorded_at')->take(20),
        ])->findOrFail($id);

        if ($request->wantsJson()) {
            return response()->json($canonical);
        }

        return view('admin.products.show', [
            'canonical' => $canonical,
        ]);
    }

    /**
     * Update canonical product metadata (name, brand, model, category).
     */
    public function update(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $canonical = CanonicalProduct::findOrFail($id);

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'brand'    => ['nullable', 'string', 'max:100'],
            'model'    => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $old = $canonical->only(['name', 'brand', 'model', 'category']);
        $canonical->update($validated);

        AdminActivityLog::record(
            'product_updated',
            "Updated canonical metadata for [{$canonical->name}] (ID: {$canonical->id})",
            $canonical,
            ['old' => $old, 'new' => $validated]
        );

        $msg = "Canonical product metadata updated.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'canonical' => $canonical, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Remove or unlink a specific group offer from this canonical product.
     */
    public function destroyOffer(Request $request, int $canonicalId, int $offerId): RedirectResponse|JsonResponse
    {
        $canonical = CanonicalProduct::findOrFail($canonicalId);
        $offer = Product::where('canonical_product_id', $canonical->id)->findOrFail($offerId);

        $offerTitle = $offer->product_name;
        $groupName  = $offer->group?->title ?? 'Unknown';

        // Detach and recalculate aggregates
        $offer->delete();

        // Refresh aggregates on canonical product
        $this->identityService->recalculateAggregates($canonical);

        AdminActivityLog::record(
            'offer_removed',
            "Removed offer [{$offerTitle}] from group '{$groupName}' from canonical [{$canonical->name}]",
            $canonical,
            ['offer_id' => $offerId, 'canonical_id' => $canonicalId]
        );

        $msg = "Group offer successfully removed and canonical metrics recalculated.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }
}
