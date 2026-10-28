<?php

namespace App\Services;

use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The single source of truth for ingesting a parsed message into the database.
 * Called by both the live listener and manual sync — no duplicated logic.
 */
class IngestMessageService
{
    public function __construct(
        private readonly TelegramClientService $telegramClient,
        private readonly ProductIdentityService $identityService,
    ) {}

    /**
     * Ingest a parsed message for a given DB group ID.
     *
     * @param  int   $groupId   The `telegram_groups.id` (not telegram_id)
     * @param  array $parsed    Output from MessageParserService::parse()
     * @param  \danog\MadelineProto\API|null $client  Pass client to download photos; null = skip
     * @return array  ['action' => 'new'|'updated'|'skipped', 'product' => Product|null]
     */
    public function ingest(int $groupId, array $parsed, $client = null): array
    {
        $productKey = $parsed['product_key'] ?? '';

        if (empty($productKey)) {
            return ['action' => 'skipped', 'product' => null];
        }

        // Download photo if media present and client provided
        $photoUrl = null;
        $photoHash = null;
        if ($client && ($parsed['has_media'] ?? false)) {
            $photoUrl = $this->telegramClient->downloadPhoto($client, $parsed['raw_message'] ?? []);
            if ($photoUrl) {
                $relPath = ltrim(str_replace('/storage/', '', $photoUrl), '/');
                $diskPath = storage_path('app/public/' . $relPath);
                $localPath = file_exists($diskPath) ? $diskPath : public_path('storage/' . $relPath);

                if (file_exists($localPath) && filesize($localPath) > 0) {
                    $cmd = 'python3 ' . base_path('phash.py') . ' ' . escapeshellarg($localPath);
                    $rawOutput = shell_exec($cmd);
                    $output = $rawOutput !== null ? trim((string) $rawOutput) : '';
                    if (strlen($output) === 16) {
                        $photoHash = $output;
                    }
                }
            }
        }

        $price       = $parsed['price'] ?? null;
        $postedAt    = $parsed['posted_at'] ?? now()->toDateTimeString();
        $messageId   = $parsed['message_id'] ?? null;
        $messageText = $parsed['message_text'] ?? '';
        $cleanedTitle = $this->identityService->cleanText($parsed['product_name'] ?? $messageText ?? $productKey);
        $productName  = !empty($cleanedTitle) ? $cleanedTitle : ($parsed['product_name'] ?? $productKey);

        // Look up existing product
        $product = Product::where('group_id', $groupId)
            ->where(function ($query) use ($photoHash, $productKey) {
                if ($photoHash) {
                    $query->where('photo_hash', $photoHash);
                }
                $query->orWhere('product_key', $productKey);
                
                // Fuzzy match for text to catch slight variations like "in stock..."
                if (mb_strlen($productKey) >= 10) {
                    $query->orWhereRaw('? LIKE CONCAT(product_key, \'%\')', [$productKey]);
                    $query->orWhere('product_key', 'like', $productKey . '%');
                }
            })
            ->first();

        if (!$product) {
            // ── NEW PRODUCT ──────────────────────────────────────────────────
            // Use firstOrCreate to safely handle any race conditions or duplicate
            // key collisions from a previous sync run without crashing the job.
            try {
                $created = DB::transaction(function () use ($groupId, $productKey, $productName, $price, $photoUrl, $photoHash, $messageId, $postedAt, $messageText) {
                    $prod = Product::firstOrCreate(
                        ['group_id' => $groupId, 'product_key' => $productKey],
                        [
                            'product_name'      => $productName,
                            'latest_price'      => $price,
                            'latest_photo_url'  => $photoUrl,
                            'photo_hash'        => $photoHash,
                            'latest_message_id' => $messageId,
                            'first_seen_at'     => $postedAt,
                            'last_updated_at'   => $postedAt,
                        ]
                    );

                    if ($prod->wasRecentlyCreated && $price !== null && $price > 0) {
                        PriceHistory::create([
                            'product_id'   => $prod->id,
                            'price'        => $price,
                            'photo_url'    => $photoUrl,
                            'message_id'   => $messageId,
                            'message_text' => $messageText,
                            'posted_at'    => $postedAt,
                            'recorded_at'  => now()->toDateTimeString(),
                        ]);
                    }

                    return $prod;
                });

                if (!$created->wasRecentlyCreated) {
                    // Already existed under a different lookup path — treat as existing
                    $product = $created;
                    goto existing_product;
                }

                $product = $created;
                $this->identityService->resolveCanonicalProduct($product, $messageText);
                Log::info("New product [{$product->id}]: {$productName}");
                $this->broadcastSseEvent('new', $product->load('group'));
                return ['action' => 'new', 'product' => $product];

            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                // A concurrent process already inserted this product — just fetch it
                $product = Product::where('group_id', $groupId)
                    ->where('product_key', $productKey)
                    ->first();

                if (!$product) {
                    Log::warning("Duplicate insert for [{$productKey}] but could not re-fetch product. Skipping.");
                    return ['action' => 'skipped', 'product' => null];
                }
            }
        }

        existing_product:

        // ── EXISTING PRODUCT ────────────────────────────────────────────────
        $priceChanged = $this->priceChanged($product->latest_price, $price);
        $photoChanged = $photoUrl && $photoUrl !== $product->latest_photo_url;
        $isNewerPost = $postedAt >= ($product->last_updated_at?->toDateTimeString() ?? '');

        if ($priceChanged) {
            // Valid price changed → update within transaction + record history
            DB::transaction(function () use ($product, $price, $photoUrl, $photoHash, $messageId, $postedAt, $messageText) {
                $updates = [
                    'latest_price'      => $price,
                    'latest_message_id' => $messageId,
                    'last_updated_at'   => $postedAt,
                ];
                if ($photoUrl) {
                    $updates['latest_photo_url'] = $photoUrl;
                }
                if ($photoHash) {
                    $updates['photo_hash'] = $photoHash;
                }

                $product->update($updates);

                PriceHistory::create([
                    'product_id'   => $product->id,
                    'price'        => $price,
                    'photo_url'    => $photoUrl ?? $product->latest_photo_url,
                    'message_id'   => $messageId,
                    'message_text' => $messageText,
                    'posted_at'    => $postedAt,
                    'recorded_at'  => now()->toDateTimeString(),
                ]);
            });

            Log::info("Price updated [{$product->id}]: {$productName} → {$price}");

            if ($product->canonicalProduct) {
                $this->identityService->refreshCanonicalStats($product->canonicalProduct);
            } else {
                $this->identityService->resolveCanonicalProduct($product, $messageText);
            }

            $this->broadcastSseEvent('updated', $product->fresh()->load('group'));
            return ['action' => 'updated', 'product' => $product->fresh()];
        }

        // Price didn't change (either same price or incoming post had no valid price).
        // If it's a newer post or photo changed, update timestamps/photo silently without duplicate history
        $updates = [];
        if ($isNewerPost) {
            $updates['last_updated_at'] = $postedAt;
            if ($messageId) {
                $updates['latest_message_id'] = $messageId;
            }
        }
        if ($photoChanged) {
            $updates['latest_photo_url'] = $photoUrl;
            if ($photoHash) {
                $updates['photo_hash'] = $photoHash;
            }
        }

        if (!empty($updates)) {
            $product->update($updates);
            if ($product->canonicalProduct) {
                $this->identityService->refreshCanonicalStats($product->canonicalProduct);
            }
        }

        return ['action' => 'skipped', 'product' => $product];
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function priceChanged(?float $existing, ?float $incoming): bool
    {
        // Only a valid, positive incoming price can constitute a price change.
        // A post without a valid price must never destroy an existing price.
        if ($incoming === null || $incoming <= 0) {
            return false;
        }

        if ($existing === null) {
            return true;
        }

        return abs($existing - $incoming) > 0.001;
    }

    /**
     * Push an SSE event by writing to a shared cache key that the SSE controller polls.
     */
    private function broadcastSseEvent(string $action, Product $product): void
    {
        try {
            $event = [
                'action'     => $action,
                'product_id' => $product->id,
                'product'    => [
                    'id'                   => $product->id,
                    'canonical_product_id' => $product->canonical_product_id,
                    'product_name'         => $product->product_name,
                    'latest_price'         => $product->latest_price,
                    'latest_photo_url'     => $product->latest_photo_url,
                    'last_updated_at'      => $product->last_updated_at?->toIso8601String(),
                    'group_title'          => $product->group?->title ?? 'Unknown',
                    'group_id'             => $product->group_id,
                ],
                'ts' => now()->toIso8601String(),
            ];

            // Push to a list in cache (SSE controller reads + clears)
            $events = Cache::get('sse_events', []);
            $events[] = $event;
            // Keep only last 50 events
            if (count($events) > 50) {
                $events = array_slice($events, -50);
            }
            Cache::put('sse_events', $events, 300); // 5 min TTL
        } catch (\Throwable $e) {
            Log::warning('SSE broadcast failed: ' . $e->getMessage());
        }
    }
}
