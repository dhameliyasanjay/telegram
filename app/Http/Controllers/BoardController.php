<?php

namespace App\Http\Controllers;

use App\Models\CanonicalProduct;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\TelegramGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    /**
     * GET /api/board
     * Unified search & multi-group price comparison endpoint.
     */
    public function index(Request $request): JsonResponse
    {
        $groupId   = $request->query('group_id');
        $search    = trim((string) $request->query('search', ''));
        $brand     = $request->query('brand');
        $minPrice  = $request->query('min_price');
        $maxPrice  = $request->query('max_price');
        $photoHash = $request->query('photo_hash');
        $sort      = $request->query('sort', 'latest');
        $perPage   = min((int) $request->query('per_page', 24), 100);

        $query = CanonicalProduct::with(['offers.group', 'cheapestGroup']);

        // 1. Photo perceptual hash filter
        if (!empty($photoHash)) {
            $query->where('photo_hash', $photoHash);
        } else {
            // 2. Text Search (Name, Brand, Model, or keywords)
            if (!empty($search)) {
                // Normalize search string: remove punctuation, extract keywords
                $cleanSearch = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $search);
                $keywords = array_filter(explode(' ', trim((string) $cleanSearch)), fn($w) => mb_strlen($w) >= 2);

                $query->where(function ($q) use ($search, $keywords) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('brand', 'like', "%{$search}%")
                      ->orWhere('model', 'like', "%{$search}%")
                      ->orWhere('canonical_key', 'like', "%{$search}%");

                    foreach ($keywords as $word) {
                        $q->orWhere('name', 'like', "%{$word}%");
                    }

                    // Also search in underlying group offers if vendor used a specific variation
                    $q->orWhereHas('offers', function ($offerQuery) use ($search) {
                        $offerQuery->where('product_name', 'like', "%{$search}%");
                    });
                });
            }

            // 3. Group filter: show only canonical products available in this group
            if (!empty($groupId)) {
                $query->whereHas('offers', fn($q) => $q->where('group_id', $groupId));
            }

            // 4. Brand filter
            if (!empty($brand)) {
                $query->where('brand', $brand);
            }

            // 5. Price range filter
            if ($minPrice !== null && is_numeric($minPrice)) {
                $query->where('cheapest_price', '>=', (float) $minPrice);
            }
            if ($maxPrice !== null && is_numeric($maxPrice)) {
                $query->where('cheapest_price', '<=', (float) $maxPrice);
            }
        }

        // 6. Sorting
        switch ($sort) {
            case 'cheapest':
            case 'price_asc':
                $query->orderByRaw('CASE WHEN cheapest_price IS NULL OR cheapest_price = 0 THEN 1 ELSE 0 END, cheapest_price ASC');
                break;
            case 'highest':
            case 'price_desc':
                $query->orderByDesc('highest_price');
                break;
            case 'offers':
                $query->orderByDesc('offers_count');
                break;
            case 'relevance':
                if (!empty($search)) {
                    $query->orderByRaw('CASE WHEN name LIKE ? THEN 1 WHEN name LIKE ? THEN 2 ELSE 3 END', ["{$search}%", "%{$search}%"]);
                } else {
                    $query->orderByDesc('last_observed_at');
                }
                break;
            case 'latest':
            default:
                $query->orderByDesc('last_observed_at');
                break;
        }

        $paginator = $query->paginate($perPage);

        $items = $paginator->map(fn(CanonicalProduct $cp) => $this->formatCanonicalProduct($cp));

        return response()->json([
            'products'     => $items,
            'total'        => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
        ]);
    }

    /**
     * GET /api/canonical-products/{id}
     * Retrieve single canonical product with full group comparison data.
     */
    public function show(int $id): JsonResponse
    {
        $cp = CanonicalProduct::with(['offers.group', 'cheapestGroup'])->findOrFail($id);

        return response()->json([
            'product' => $this->formatCanonicalProduct($cp),
        ]);
    }

    /**
     * Format a canonical product model with compared group offers.
     */
    public function formatCanonicalProduct(CanonicalProduct $cp): array
    {
        $cheapestPrice = $cp->cheapest_price;

        // Sort offers: cheapest first, tie-break by latest update
        $sortedOffers = $cp->offers->sortBy(function (Product $offer) use ($cheapestPrice) {
            $price = $offer->latest_price;
            $isPriced = ($price !== null && $price > 0) ? 0 : 1;
            return sprintf('%d_%012.2f', $isPriced, (float) $price);
        })->values();

        $formattedOffers = $sortedOffers->map(function (Product $o) use ($cheapestPrice) {
            $price = $o->latest_price;
            $diff = null;
            $isCheapest = false;

            if ($price !== null && $cheapestPrice !== null && $price > 0) {
                $diff = round($price - $cheapestPrice, 2);
                $isCheapest = (abs($price - $cheapestPrice) < 0.001);
            }

            // Construct Telegram message link if possible
            $tgLink = $o->group?->invite_link;
            if ($tgLink && $o->latest_message_id) {
                $username = ltrim(preg_replace('#^https?://t\.me/#', '', $tgLink), '@');
                if (!str_contains($username, '+') && !str_contains($username, 'joinchat')) {
                    $tgLink = "https://t.me/{$username}/{$o->latest_message_id}";
                }
            }

            return [
                'id'               => $o->id,
                'group_id'         => $o->group_id,
                'group_title'      => $o->group?->title ?? 'Unknown',
                'group_link'       => $tgLink,
                'group_phone'      => $o->group?->phone,
                'group_location'   => $o->group?->location,
                'group_address'    => $o->group?->address,
                'member_count'     => $o->group?->member_count ?? 0,
                'latest_price'     => $price,
                'price_diff'       => $diff,
                'is_cheapest'      => $isCheapest,
                'latest_photo_url' => $o->latest_photo_url,
                'latest_message_id'=> $o->latest_message_id,
                'last_updated_at'  => $o->last_updated_at?->toIso8601String(),
                'first_seen_at'    => $o->first_seen_at?->toIso8601String(),
            ];
        });

        return [
            'id'                 => $cp->id,
            'canonical_key'      => $cp->canonical_key,
            'product_name'       => $cp->name,
            'brand'              => $cp->brand,
            'model'              => $cp->model,
            'category'           => $cp->category,
            'primary_photo_url'  => $cp->primary_photo_url,
            'photo_hash'         => $cp->photo_hash,
            'cheapest_price'     => $cp->cheapest_price,
            'highest_price'      => $cp->highest_price,
            'offers_count'       => $cp->offers_count,
            'cheapest_group'     => [
                'id'    => $cp->cheapestGroup?->id,
                'title' => $cp->cheapestGroup?->title ?? 'Unknown',
            ],
            'last_observed_at'   => $cp->last_observed_at?->toIso8601String(),
            'offers'             => $formattedOffers,
        ];
    }

    /**
     * GET /api/board/stats
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'total_groups'             => TelegramGroup::count(),
            'total_canonical_products' => CanonicalProduct::count(),
            'total_products'           => CanonicalProduct::count(), // backward compatibility
            'total_offers'             => Product::count(),
            'multi_group_products'     => CanonicalProduct::where('offers_count', '>', 1)->count(),
            'updated_today'            => Product::whereDate('last_updated_at', today())->count(),
            'with_price'               => Product::whereNotNull('latest_price')->where('latest_price', '>', 0)->count(),
        ]);
    }

    /**
     * GET /api/products/{id}/history
     */
    public function history(int $id): JsonResponse
    {
        // $id can be either a group offer Product ID or a CanonicalProduct ID
        $product = Product::with('group')->find($id);

        if (!$product) {
            // Fallback: check if $id is a CanonicalProduct ID and find its primary offer
            $canonical = CanonicalProduct::findOrFail($id);
            $product = $canonical->offers()->with('group')->firstOrFail();
        }

        $history = PriceHistory::where('product_id', $product->id)
            ->orderByDesc('posted_at')
            ->limit(50)
            ->get()
            ->map(fn(PriceHistory $h) => [
                'price'        => $h->price,
                'photo_url'    => $h->photo_url,
                'message_text' => $h->message_text,
                'posted_at'    => $h->posted_at?->toIso8601String(),
            ]);

        return response()->json([
            'product' => [
                'id'           => $product->id,
                'product_name' => $product->product_name,
                'group_title'  => $product->group?->title,
            ],
            'history' => $history,
        ]);
    }

    /**
     * POST /api/board/hash-photo
     * Computes the perceptual hash of an uploaded photo for visual filtering.
     */
    public function hashPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => 'required|file|image|max:10240', // 10MB max
        ]);

        $file = $request->file('photo');
        $cmd = 'python3 ' . base_path('phash.py') . ' ' . escapeshellarg($file->getRealPath());
        $rawOutput = shell_exec($cmd);
        $hash = $rawOutput !== null ? trim((string) $rawOutput) : '';

        if (strlen($hash) !== 16) {
            return response()->json(['message' => 'Failed to process image'], 500);
        }

        return response()->json([
            'photo_hash' => $hash,
        ]);
    }
}
