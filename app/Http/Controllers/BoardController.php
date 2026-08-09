<?php

namespace App\Http\Controllers;

use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\TelegramGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    /**
     * GET /api/board
     * Returns all products across all groups, sorted by last_updated_at desc.
     */
    public function index(Request $request): JsonResponse
    {
        $groupId = $request->query('group_id');
        $search  = $request->query('search');
        $perPage = min((int) $request->query('per_page', 50), 200);

        $photoHash = $request->query('photo_hash');

        $query = Product::with('group')
            ->orderByDesc('last_updated_at');

        if ($photoHash) {
            $query->where('photo_hash', $photoHash);
        } else {
            if ($groupId) {
                $query->where('group_id', $groupId);
            }

            if ($search) {
                $query->where('product_name', 'like', "%{$search}%");
            }
        }

        $products = $query->paginate($perPage);

        $items = $products->map(fn(Product $p) => [
            'id'               => $p->id,
            'product_name'     => $p->product_name,
            'latest_price'     => $p->latest_price,
            'latest_photo_url' => $p->latest_photo_url,
            'last_updated_at'  => $p->last_updated_at?->toIso8601String(),
            'first_seen_at'    => $p->first_seen_at?->toIso8601String(),
            'group' => [
                'id'           => $p->group?->id,
                'title'        => $p->group?->title ?? 'Unknown',
                'member_count' => $p->group?->member_count,
            ],
        ]);

        return response()->json([
            'products'    => $items,
            'total'       => $products->total(),
            'current_page'=> $products->currentPage(),
            'last_page'   => $products->lastPage(),
        ]);
    }

    /**
     * GET /api/board/stats
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'total_groups'   => TelegramGroup::count(),
            'total_products' => Product::count(),
            'updated_today'  => Product::whereDate('last_updated_at', today())->count(),
            'with_price'     => Product::whereNotNull('latest_price')->count(),
        ]);
    }

    /**
     * GET /api/products/{id}/history
     */
    public function history(int $id): JsonResponse
    {
        $product = Product::with('group')->findOrFail($id);
        $history = PriceHistory::where('product_id', $id)
            ->orderByDesc('posted_at')
            ->limit(50)
            ->get()
            ->map(fn(PriceHistory $h) => [
                'price'       => $h->price,
                'photo_url'   => $h->photo_url,
                'message_text'=> $h->message_text,
                'posted_at'   => $h->posted_at?->toIso8601String(),
            ]);

        return response()->json([
            'product' => [
                'id'          => $product->id,
                'product_name'=> $product->product_name,
                'group_title' => $product->group?->title,
            ],
            'history' => $history,
        ]);
    }

    /**
     * POST /api/board/hash-photo
     * Computes the SHA-256 hash of an uploaded photo for filtering.
     */
    public function hashPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => 'required|file|image|max:10240', // 10MB max
        ]);

        $file = $request->file('photo');
        $cmd = 'python3 ' . base_path('phash.py') . ' ' . escapeshellarg($file->getRealPath());
        $hash = trim(shell_exec($cmd));

        if (strlen($hash) !== 16) {
            return response()->json(['message' => 'Failed to process image'], 500);
        }

        return response()->json([
            'photo_hash' => $hash,
        ]);
    }
}
