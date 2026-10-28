<?php

namespace App\Services;

use App\Models\CanonicalProduct;
use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductIdentityService
{
    private const KNOWN_BRANDS = [
        'nima', 'milton', 'cello', 'tupperware', 'philips', 'syska', 'basketo',
        'havells', 'borosil', 'prestige', 'pigeon', 'bajaj', 'butterfly', 'wonderchef',
        'nova', 'kemei', 'vgr', 'oraimo', 'boat', 'noise', 'fire-boltt', 'boult',
        'realme', 'mi', 'xiaomi', 'oneplus', 'samsung', 'apple', 'zebronics', 'intex',
        'ambrane', 'ptron', 'portronics', 'ubon', 'corseca', 'gizmore',
    ];

    private const STOP_WORDS = [
        'the', 'and', 'with', 'for', 'from', 'best', 'quality', 'product', 'products',
        'wholesale', 'rate', 'rates', 'price', 'prices', 'new', 'hot', 'online', 'store',
        'delivery', 'stock', 'available', 'ready', 'item', 'items', 'imported', 'china',
        'heavy', 'duty', 'premium', 'high', 'trending', 'viral', 'stylish', 'multipurpose',
        'multi', 'purpose', 'super', 'deal', 'offer', 'brand', 'bazaar', 'mart', 'hub',
    ];

    /**
     * Clean and normalize raw text, stripping vendor promotional boilerplate.
     */
    public function cleanText(string $rawText): string
    {
        // 1. Replace non-breaking spaces and unicode spaces
        $clean = str_replace(["\xc2\xa0", "\u{00a0}", "\t"], ' ', $rawText);

        // 2. Remove URLs (http, t.me, wa.me, etc.)
        $clean = preg_replace('#https?://\S+#i', '', $clean);
        $clean = preg_replace('#(?:t\.me|wa\.me|chat\.whatsapp\.com)/\S+#i', '', $clean);

        // 3. Remove Indian phone numbers (e.g. 9099065966 | 9429089014)
        $clean = preg_replace('/(?:\+?91[\s\-]?)?[6-9]\d{9}/', '', $clean);

        // 4. Split into lines and reject promotional boilerplate lines
        $lines = preg_split('/[\r\n]+/', $clean);
        $meaningfulLines = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if ($this->isPromotionalLine($line)) {
                continue;
            }

            // Remove trailing price patterns
            $line = $this->stripTrailingPrice($line);
            if (!empty($line) && mb_strlen($line) >= 3) {
                $meaningfulLines[] = $line;
            }
        }

        if (empty($meaningfulLines)) {
            // Fallback: take original first line cleaned of prices
            $first = $lines[0] ?? $rawText;
            return mb_substr($this->stripTrailingPrice(trim($first)), 0, 200);
        }

        return $meaningfulLines[0];
    }

    /**
     * Check if a line is promotional filler rather than a product name.
     */
    public function isPromotionalLine(string $line): bool
    {
        $normalized = mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $line));
        $normalized = preg_replace('/\s+/', ' ', trim($normalized));

        $promotionalPatterns = [
            'in stock', 'best rate', 'ready stock', 'call for bulk', 'ask for best',
            'ask me carton', 'best quantity', 'join our telegram', 'join our whatsapp',
            'katargam branch', 'yogichok branch', 'katargam', 'yogichok', 'mute', 'unmute',
            'share this post', 'order now', 'dm for best', 'ctn rate', 'reddy delivery',
            'all color available', 'all color avilable', 'cash on delivery', 'cod available',
            'best quality products at wholesale', 'wholesale prices', 'ecommerce wholesaler',
            'call and message', 'whatsapp message', 'dom 1 royal dom', 'opp moni international',
            'sahyog society', 'surat gujarat',
        ];

        foreach ($promotionalPatterns as $pattern) {
            if (str_contains($normalized, $pattern)) {
                return true;
            }
        }

        // Check for code / carton quantity headers like "code = 2610" or "1 ctn pcs = 500"
        if (preg_match('/^(?:code|item\s*code|sku)?\s*[:=\-]?\s*\d+$/i', $line)) {
            return true;
        }
        if (preg_match('/^\d+\s*(?:ctn|pcs|piece|box|carton)\s*(?:pcs)?\s*[:=\-]?\s*\d*$/i', $line)) {
            return true;
        }

        return false;
    }

    /**
     * Strip trailing price notation from a line.
     */
    public function stripTrailingPrice(string $line): string
    {
        // Strip emoji and decorative bullets from ends
        $line = preg_replace('/^[\p{Emoji}\s\-\*•▪►★✨⚡|~#]+/u', '', $line);
        $line = preg_replace('/[\p{Emoji}\s\-\*•▪►★✨⚡|~#]+$/u', '', $line);

        // Strip price matches at the end of the line
        $pricePatterns = [
            '/(?:\s*[\-\–])?\s*[\$₹€£]\s*[\d,]+(?:\.\d+)?\s*(?:\/\-)?\s*$/iu',
            '/(?:\s*[\-\–])?\s*[\d,]+(?:\.\d+)?\s*(?:rs\.?|inr|rupees?|\/\-|\/)\s*$/iu',
            '/(?:\s*[\-\–])?\s*(?:rate|price|sp|mrp)[\s:=]+[\$₹€£]?\s*[\d,]+(?:\.\d+)?\s*(?:\/\-)?\s*$/iu',
        ];

        foreach ($pricePatterns as $pattern) {
            $line = preg_replace($pattern, '', $line);
        }

        return trim($line);
    }

    /**
     * Extract recognizable brand from text.
     */
    public function extractBrand(string $text): ?string
    {
        // Check explicit brand label
        if (preg_match('/\bbrand\s*[:=\-]\s*([A-Za-z0-9\-]+)/i', $text, $m)) {
            return ucfirst(strtolower(trim($m[1])));
        }

        $lower = strtolower($text);
        foreach (self::KNOWN_BRANDS as $brand) {
            if (preg_match('/\b' . preg_quote($brand, '/') . '\b/i', $lower)) {
                return ucfirst($brand);
            }
        }

        return null;
    }

    /**
     * Extract model number or SKU identifier.
     */
    public function extractModelOrSku(string $text): ?string
    {
        // Match bracketed codes like [ TA - 72 ] or [TA-72]
        if (preg_match('/\[\s*([A-Z]{1,4}\s*[\-\_]?\s*\d{1,5})\s*\]/i', $text, $m)) {
            return strtoupper(preg_replace('/\s+/', '', $m[1]));
        }

        // Match "Code = 2610" or "Model: XYZ-100"
        if (preg_match('/(?:model|sku|code|item\s*code)\s*[:=\-#]\s*([A-Za-z0-9\-_]{2,15})/i', $text, $m)) {
            return strtoupper(trim($m[1]));
        }

        // Match alphanumeric model codes like KM-6630, V-030, SK-801, T-9
        if (preg_match('/\b([A-Za-z]{1,4}[\-][A-Za-z0-9]{2,6})\b/i', $text, $m)) {
            return strtoupper(trim($m[1]));
        }

        return null;
    }

    /**
     * Extract critical variant specifications that differentiate variants.
     */
    public function extractSpecifications(string $text): array
    {
        $specs = [];

        // Dimensions: 60*200 cm, 60x200cm, 6*4
        if (preg_match('/(\d+(?:\.\d+)?\s*(?:[\*xX]|by)\s*\d+(?:\.\d+)?(?:\s*(?:cm|mm|inch|feet|ft|m))?)/i', $text, $m)) {
            $specs['dimensions'] = strtolower(preg_replace('/\s+/', '', $m[1]));
        }

        // Liquid volume / weight: 1000ml, 500ml, 1kg, 500g
        if (preg_match('/\b(\d+(?:\.\d+)?\s*(?:ml|l|ltr|liters?|kg|gm|g))\b/i', $text, $m)) {
            $specs['volume'] = strtolower(preg_replace('/\s+/', '', $m[1]));
        }

        // Thickness / precision: 1.0mm, 0.5mm
        if (preg_match('/\b(\d+\.\d+\s*(?:mm|cm))\b/i', $text, $m)) {
            $specs['thickness'] = strtolower(preg_replace('/\s+/', '', $m[1]));
        }

        // Multi-pack / items: 16 in 1, 48 pcs, 12pc, pack of 2
        if (preg_match('/\b(\d+\s*in\s*1)\b/i', $text, $m)) {
            $specs['multi'] = strtolower(preg_replace('/\s+/', '', $m[1]));
        } elseif (preg_match('/\b(\d+\s*(?:pcs|pc|pack|set))\b/i', $text, $m)) {
            $specs['pack'] = strtolower(preg_replace('/\s+/', '', $m[1]));
        }

        // Size descriptors: big size, small size
        if (preg_match('/\b(big|small|medium|large|mini|jumbo)\s*size\b/i', $text, $m)) {
            $specs['size'] = strtolower($m[1]);
        }

        return $specs;
    }

    /**
     * Generate a stable, deterministic canonical key for cross-group product identity.
     */
    public function generateCanonicalKey(string $name, ?string $rawText = null, ?string $photoHash = null): string
    {
        $context = $name . ' ' . ($rawText ?? '');

        $brand = $this->extractBrand($context);
        $model = $this->extractModelOrSku($context);
        $specs = $this->extractSpecifications($context);

        // Clean name to core alphanumeric tokens
        $clean = mb_strtolower($name);
        $clean = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $clean);
        $tokens = preg_split('/\s+/', trim($clean));

        // Filter stop words and tiny tokens
        $coreTokens = [];
        foreach ($tokens as $token) {
            if (strlen($token) >= 2 && !in_array($token, self::STOP_WORDS, true)) {
                $coreTokens[] = $token;
            }
        }

        // Sort unique core tokens alphabetically for order-agnostic matching
        $uniqueTokens = array_values(array_unique($coreTokens));
        sort($uniqueTokens);

        // Build deterministic canonical fingerprint
        $parts = [];
        if ($brand) {
            $parts[] = 'b:' . strtolower($brand);
        }
        if ($model) {
            $parts[] = 'm:' . strtolower($model);
        }
        foreach ($specs as $k => $v) {
            $parts[] = substr($k, 0, 1) . ':' . $v;
        }

        // If core tokens exist, take up to 8 most relevant tokens
        $tokenSlice = array_slice($uniqueTokens, 0, 8);
        $tokenStr = implode('-', $tokenSlice);

        if (!empty($tokenStr)) {
            $parts[] = 't:' . $tokenStr;
        }

        $canonicalSignature = implode('|', $parts);

        // Fallback for extremely sparse text
        if (empty($canonicalSignature)) {
            $canonicalSignature = 'raw:' . Str::slug(mb_substr($name, 0, 50));
        }

        // Hash to fit 255 char DB key with readable prefix
        $readablePrefix = Str::slug(mb_substr(($brand ? $brand . '-' : '') . implode(' ', $tokenSlice), 0, 60));
        $hash = substr(hash('xxh64', $canonicalSignature), 0, 16);

        return $readablePrefix ? "{$readablePrefix}-{$hash}" : "prod-{$hash}";
    }

    private array $canonicalCache = [];

    /**
     * Resolve or create a CanonicalProduct for an individual group offer (Product).
     */
    public function resolveCanonicalProduct(Product $product, ?string $rawText = null, bool $refreshStats = true): CanonicalProduct
    {
        $cleanedName = $this->cleanText($product->product_name);
        if (empty($cleanedName)) {
            $cleanedName = $product->product_name;
        }

        $brand = $this->extractBrand($cleanedName . ' ' . ($rawText ?? ''));
        $model = $this->extractModelOrSku($cleanedName . ' ' . ($rawText ?? ''));
        $canonicalKey = $this->generateCanonicalKey($cleanedName, $rawText, $product->photo_hash);

        // 1. First, check memory cache or database
        $canonical = $this->canonicalCache[$canonicalKey]
            ?? CanonicalProduct::where('canonical_key', $canonicalKey)->first();

        // 2. If not found by key, check by exact non-null photo_hash if available
        if (!$canonical && !empty($product->photo_hash) && strlen($product->photo_hash) === 16) {
            $canonical = CanonicalProduct::where('photo_hash', $product->photo_hash)->first();
        }

        // 3. Create canonical product if not exists
        if (!$canonical) {
            $canonical = CanonicalProduct::create([
                'canonical_key'     => $canonicalKey,
                'name'              => $cleanedName,
                'brand'             => $brand,
                'model'             => $model,
                'primary_photo_url' => $product->latest_photo_url,
                'photo_hash'        => $product->photo_hash,
                'cheapest_price'    => $product->latest_price,
                'cheapest_group_id' => $product->group_id,
                'highest_price'     => $product->latest_price,
                'offers_count'      => 1,
                'last_observed_at'  => $product->last_updated_at ?? now(),
            ]);
        }

        $this->canonicalCache[$canonicalKey] = $canonical;

        // Associate offer with canonical product
        $product->canonical_product_id = $canonical->id;
        $product->save();

        // Refresh stats on the canonical product if requested
        if ($refreshStats) {
            $this->refreshCanonicalStats($canonical);
        }

        return $canonical;
    }

    /**
     * Refresh aggregated stats across all canonical products in batch.
     */
    public function refreshAllCanonicalStats(): void
    {
        $aggregates = Product::select(
            'canonical_product_id',
            DB::raw('COUNT(*) as total_offers'),
            DB::raw('MIN(CASE WHEN latest_price > 0 THEN latest_price ELSE NULL END) as min_price'),
            DB::raw('MAX(CASE WHEN latest_price > 0 THEN latest_price ELSE NULL END) as max_price'),
            DB::raw('MAX(last_updated_at) as latest_seen')
        )
        ->whereNotNull('canonical_product_id')
        ->groupBy('canonical_product_id')
        ->get();

        foreach ($aggregates->chunk(500) as $chunk) {
            foreach ($chunk as $agg) {
                $cheapestGroup = null;
                if ($agg->min_price !== null) {
                    $cheapestGroup = Product::where('canonical_product_id', $agg->canonical_product_id)
                        ->where('latest_price', $agg->min_price)
                        ->value('group_id');
                }

                CanonicalProduct::where('id', $agg->canonical_product_id)->update([
                    'offers_count'      => (int) $agg->total_offers,
                    'cheapest_price'    => $agg->min_price !== null ? (float) $agg->min_price : null,
                    'highest_price'     => $agg->max_price !== null ? (float) $agg->max_price : null,
                    'cheapest_group_id' => $cheapestGroup,
                    'last_observed_at'  => $agg->latest_seen,
                ]);
            }
        }
    }

    /**
     * Refresh aggregated pricing and offer counts for a canonical product across all its group offers.
     */
    public function refreshCanonicalStats(CanonicalProduct $canonical): void
    {
        $offers = Product::where('canonical_product_id', $canonical->id)->get();
        $offersCount = $offers->count();

        $pricedOffers = $offers->filter(fn($p) => $p->latest_price !== null && $p->latest_price > 0);

        $cheapestOffer = $pricedOffers->sortBy('latest_price')->first();
        $highestOffer  = $pricedOffers->sortByDesc('latest_price')->first();

        $latestObserved = $offers->max('last_updated_at') ?? $canonical->last_observed_at;

        // Choose primary photo (prefer the cheapest offer's photo, or the latest available photo)
        $primaryPhoto = $cheapestOffer?->latest_photo_url
            ?? $offers->firstWhere('latest_photo_url', '!=', null)?->latest_photo_url
            ?? $canonical->primary_photo_url;

        $photoHash = $cheapestOffer?->photo_hash
            ?? $offers->firstWhere('photo_hash', '!=', null)?->photo_hash
            ?? $canonical->photo_hash;

        $canonical->update([
            'cheapest_price'    => $cheapestOffer?->latest_price,
            'cheapest_group_id' => $cheapestOffer?->group_id,
            'highest_price'     => $highestOffer?->latest_price,
            'offers_count'      => $offersCount,
            'primary_photo_url' => $primaryPhoto,
            'photo_hash'        => $photoHash,
            'last_observed_at'  => $latestObserved,
        ]);
    }

    /**
     * Enforce Rule A within a single group: exactly one active offer per group for each canonical product.
     * Merges redundant duplicate offers in the same group into the primary offer and deletes the duplicate row.
     */
    public function deduplicateGroupOffers(int $groupId): int
    {
        $duplicates = Product::select('canonical_product_id', DB::raw('count(*) as count'))
            ->where('group_id', $groupId)
            ->whereNotNull('canonical_product_id')
            ->groupBy('canonical_product_id')
            ->having('count', '>', 1)
            ->get();

        $mergedCount = 0;

        foreach ($duplicates as $dup) {
            $groupOffers = Product::where('group_id', $groupId)
                ->where('canonical_product_id', $dup->canonical_product_id)
                ->orderByDesc('last_updated_at')
                ->get();

            if ($groupOffers->count() <= 1) continue;

            $primary = $groupOffers->first();
            $redundant = $groupOffers->slice(1);

            DB::transaction(function () use ($primary, $redundant, &$mergedCount) {
                foreach ($redundant as $other) {
                    // Re-parent price history to the primary offer
                    PriceHistory::where('product_id', $other->id)
                        ->update(['product_id' => $primary->id]);

                    // Preserve earliest first_seen_at
                    if ($other->first_seen_at && ($primary->first_seen_at === null || $other->first_seen_at < $primary->first_seen_at)) {
                        $primary->first_seen_at = $other->first_seen_at;
                    }

                    // Preserve photo if primary lacks one
                    if (empty($primary->latest_photo_url) && !empty($other->latest_photo_url)) {
                        $primary->latest_photo_url = $other->latest_photo_url;
                        $primary->photo_hash = $other->photo_hash;
                    }

                    $other->delete();
                    $mergedCount++;
                }

                $primary->save();
            });
        }

        return $mergedCount;
    }
}
