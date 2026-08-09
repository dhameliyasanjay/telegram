<?php

namespace App\Services;

use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * The single source of truth for ingesting a parsed message into the database.
 * Called by both the live listener and manual sync — no duplicated logic.
 */
class IngestMessageService
{
    public function __construct(
        private readonly TelegramClientService $telegramClient,
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
                $localPath = public_path(str_replace('/storage/', 'storage/', $photoUrl));
                if (file_exists($localPath)) {
                    $cmd = 'python3 ' . base_path('phash.py') . ' ' . escapeshellarg($localPath);
                    $output = trim(shell_exec($cmd));
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
        $productName = $parsed['product_name'] ?? $productKey;

        // Look up existing product
        if ($photoHash) {
            $product = Product::where('group_id', $groupId)
                ->where('photo_hash', $photoHash)
                ->first();
        } else {
            $product = Product::where('group_id', $groupId)
                ->where('product_key', $productKey)
                ->first();
        }

        if (!$product) {
            // ── NEW PRODUCT ──────────────────────────────────────────────────
            $product = Product::create([
                'group_id'          => $groupId,
                'product_key'       => $productKey,
                'product_name'      => $productName,
                'latest_price'      => $price,
                'latest_photo_url'  => $photoUrl,
                'photo_hash'        => $photoHash,
                'latest_message_id' => $messageId,
                'first_seen_at'     => $postedAt,
                'last_updated_at'   => $postedAt,
            ]);

            PriceHistory::create([
                'product_id'  => $product->id,
                'price'       => $price,
                'photo_url'   => $photoUrl,
                'message_id'  => $messageId,
                'message_text'=> $messageText,
                'posted_at'   => $postedAt,
                'recorded_at' => now()->toDateTimeString(),
            ]);

            Log::info("New product [{$product->id}]: {$productName}");
            $this->broadcastSseEvent('new', $product->load('group'));
            return ['action' => 'new', 'product' => $product];
        }

        // ── EXISTING PRODUCT ────────────────────────────────────────────────
        $priceChanged = $this->priceChanged($product->latest_price, $price);
        $photoChanged = $photoUrl && $photoUrl !== $product->latest_photo_url;

        if (!$priceChanged && !$photoChanged) {
            return ['action' => 'skipped', 'product' => $product];
        }

        if ($priceChanged) {
            // Price changed → update + record history
            $product->update([
                'latest_price'      => $price,
                'latest_photo_url'  => $photoUrl ?? $product->latest_photo_url,
                'latest_message_id' => $messageId,
                'last_updated_at'   => $postedAt,
            ]);

            PriceHistory::create([
                'product_id'  => $product->id,
                'price'       => $price,
                'photo_url'   => $photoUrl,
                'message_id'  => $messageId,
                'message_text'=> $messageText,
                'posted_at'   => $postedAt,
                'recorded_at' => now()->toDateTimeString(),
            ]);

            Log::info("Price updated [{$product->id}]: {$productName} → {$price}");
            $this->broadcastSseEvent('updated', $product->fresh()->load('group'));
            return ['action' => 'updated', 'product' => $product->fresh()];
        }

        // Only photo changed — update silently, no history entry
        $product->update([
            'latest_photo_url' => $photoUrl,
            'photo_hash' => $photoHash,
        ]);
        return ['action' => 'skipped', 'product' => $product];
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function priceChanged(?float $existing, ?float $incoming): bool
    {
        if ($existing === null && $incoming === null) return false;
        if ($existing === null || $incoming === null) return true;
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
                    'id'               => $product->id,
                    'product_name'     => $product->product_name,
                    'latest_price'     => $product->latest_price,
                    'latest_photo_url' => $product->latest_photo_url,
                    'last_updated_at'  => $product->last_updated_at?->toIso8601String(),
                    'group_title'      => $product->group?->title ?? 'Unknown',
                    'group_id'         => $product->group_id,
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
