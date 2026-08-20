<?php

namespace App\Http\Controllers;

use App\Models\CatalogueProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CatalogueController extends Controller
{
    // ── Web page ────────────────────────────────────────────────────────────
    public function index()
    {
        return view('catalogue');
    }

    // ── API: list ───────────────────────────────────────────────────────────
    public function apiIndex(Request $request): JsonResponse
    {
        $search  = $request->query('search');
        $status  = $request->query('status');
        $perPage = min((int) $request->query('per_page', 30), 200);

        $query = CatalogueProduct::orderByDesc('created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('sku_id', 'like', "%{$search}%")
                  ->orWhere('group_name', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $results = $query->paginate($perPage);

        return response()->json([
            'data'         => $results->items(),
            'total'        => $results->total(),
            'current_page' => $results->currentPage(),
            'last_page'    => $results->lastPage(),
        ]);
    }

    // ── API: single ─────────────────────────────────────────────────────────
    public function show(int $id): JsonResponse
    {
        $product = CatalogueProduct::findOrFail($id);
        return response()->json($product);
    }

    // ── API: create ─────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('catalogue', 'public');
        }

        $sku = $request->input('sku_id') ?: $this->generateSku($request->input('product_name', ''));

        $product = CatalogueProduct::create([
            'photo_path'       => $photoPath,
            'product_name'     => $request->input('product_name'),
            'sku_id'           => $sku,
            'bank_settlement'  => $request->input('bank_settlement') ?: null,
            'market_price'     => $request->input('market_price') ?: null,
            'qty'              => $request->input('qty') ?: null,
            'group_name'       => $this->parseGroupNames($request),
            'status'           => $request->input('status', 'pending'),
        ]);

        return response()->json($product, 201);
    }

    // ── API: update ─────────────────────────────────────────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $product = CatalogueProduct::findOrFail($id);

        $photoPath = $product->photo_path;
        if ($request->hasFile('photo')) {
            // Delete old photo
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = $request->file('photo')->store('catalogue', 'public');
        }

        $product->update([
            'photo_path'      => $photoPath,
            'product_name'    => $request->input('product_name', $product->product_name),
            'sku_id'          => $request->input('sku_id', $product->sku_id),
            'bank_settlement' => $request->input('bank_settlement') ?: null,
            'market_price'    => $request->input('market_price') ?: null,
            'qty'             => $request->input('qty') ?: null,
            'group_name'      => $this->parseGroupNames($request) ?? $product->group_name,
            'status'          => $request->input('status', $product->status),
        ]);

        return response()->json($product->fresh());
    }

    // ── API: delete ─────────────────────────────────────────────────────────
    public function destroy(int $id): JsonResponse
    {
        $product = CatalogueProduct::findOrFail($id);
        if ($product->photo_path) {
            Storage::disk('public')->delete($product->photo_path);
        }
        $product->delete();
        return response()->json(['message' => 'Deleted successfully.']);
    }

    // ── API: SKU preview ────────────────────────────────────────────────────
    public function skuPreview(Request $request): JsonResponse
    {
        $name = $request->query('name', '');
        return response()->json(['sku' => $this->generateSku($name)]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────
    /**
     * Parse group_name from request — accepts JSON string or array.
     */
    private function parseGroupNames(Request $request): ?array
    {
        $raw = $request->input('group_name');
        if (is_null($raw) || $raw === '' || $raw === '[]') {
            return null;
        }
        // Already an array (multipart/form-data with group_name[])
        if (is_array($raw)) {
            return array_values(array_filter($raw));
        }
        // JSON string sent from JS
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_filter($decoded));
        }
        // Plain single string fallback
        return [$raw];
    }

    private function generateSku(string $name): string
    {
        // Strip non-alpha chars, take first 6 uppercase chars
        $slug = strtoupper(preg_replace('/[^A-Za-z]/', '', $name));
        $slug = substr($slug, 0, 6);
        if (empty($slug)) {
            $slug = 'PROD';
        }

        // Count existing with same slug prefix to make a unique counter
        $prefix  = "JK-{$slug}-";
        $count   = CatalogueProduct::where('sku_id', 'like', "{$prefix}%")->count();
        $counter = str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        $candidate = "{$prefix}{$counter}";

        // Ensure uniqueness (rare collision guard)
        while (CatalogueProduct::where('sku_id', $candidate)->exists()) {
            $count++;
            $counter   = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            $candidate = "{$prefix}{$counter}";
        }

        return $candidate;
    }
}
