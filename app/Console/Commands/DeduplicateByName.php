<?php

namespace App\Console\Commands;

use App\Models\PriceHistory;
use App\Models\Product;
use App\Services\MessageParserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeduplicateByName extends Command
{
    protected $signature = 'app:deduplicate-by-name';
    protected $description = 'Backfill product keys using product names and merge duplicates';

    public function handle(MessageParserService $parser)
    {
        $this->info('Finding duplicates by new product name key...');

        $products = Product::all();
        $grouped = [];

        $bar = $this->output->createProgressBar($products->count());
        foreach ($products as $product) {
            $history = PriceHistory::where('product_id', $product->id)->first();
            if ($history && $history->message_text) {
                $newKey = $parser->buildProductKey($history->message_text);
            } else {
                $newKey = $parser->buildProductKey($product->product_name);
            }
            $grouped[$product->group_id][$newKey][] = $product;
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $mergedCount = 0;
        $deletedCount = 0;

        $this->info('Merging duplicates...');
        foreach ($grouped as $groupId => $keys) {
            foreach ($keys as $newKey => $dupeProductsList) {
                if (count($dupeProductsList) > 1) {
                    // Sort by first_seen_at asc
                    usort($dupeProductsList, fn($a, $b) => $a->first_seen_at <=> $b->first_seen_at);
                    
                    $primary = $dupeProductsList[0];
                    $others = array_slice($dupeProductsList, 1);

                    foreach ($others as $other) {
                        PriceHistory::where('product_id', $other->id)
                            ->update(['product_id' => $primary->id]);

                        if ($other->last_updated_at > $primary->last_updated_at) {
                            $primary->latest_price = $other->latest_price;
                            $primary->latest_photo_url = $other->latest_photo_url;
                            $primary->photo_hash = $other->photo_hash;
                            $primary->latest_message_id = $other->latest_message_id;
                            $primary->last_updated_at = $other->last_updated_at;
                        }

                        $other->delete();
                        $deletedCount++;
                    }
                    // Save primary if it was modified
                    $primary->product_key = $newKey;
                    $primary->save();
                    
                    $mergedCount++;
                } else {
                    // Just update the key if needed
                    $single = $dupeProductsList[0];
                    if ($single->product_key !== $newKey) {
                        $single->update(['product_key' => $newKey]);
                    }
                }
            }
        }

        $this->info("Complete. Merged {$mergedCount} groups of duplicates, deleted {$deletedCount} duplicate products.");
    }
}
