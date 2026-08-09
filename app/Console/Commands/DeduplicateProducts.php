<?php

namespace App\Console\Commands;

use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeduplicateProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:deduplicate-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate photo hashes for all products and deduplicate identical photos within groups';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting deduplication process...');

        $products = Product::whereNotNull('latest_photo_url')->get();
        $this->info("Found {$products->count()} products with photos.");

        $bar = $this->output->createProgressBar($products->count());
        
        $pathsToProducts = [];
        $missingFiles = 0;
        foreach ($products as $product) {
            $path = public_path(str_replace('/storage/', 'storage/', $product->latest_photo_url));
            if (file_exists($path)) {
                $pathsToProducts[$path] = $product;
            } else {
                $missingFiles++;
            }
        }
        
        $paths = array_keys($pathsToProducts);
        $chunkSize = 100;
        $totalChunks = ceil(count($paths) / $chunkSize);
        
        $this->info("Batch hashing " . count($paths) . " photos in {$totalChunks} chunks...");
        $bar = $this->output->createProgressBar(count($paths));

        foreach (array_chunk($paths, $chunkSize) as $chunk) {
            $jsonInput = json_encode($chunk);
            $cmd = 'python3 ' . base_path('batch_phash.py');
            
            $descriptorspec = [
               0 => ["pipe", "r"],  // stdin
               1 => ["pipe", "w"],  // stdout
               2 => ["pipe", "w"]   // stderr
            ];
            
            $process = proc_open($cmd, $descriptorspec, $pipes);
            
            if (is_resource($process)) {
                fwrite($pipes[0], $jsonInput);
                fclose($pipes[0]);
                
                $output = stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);
                
                $results = json_decode($output, true);
                if (is_array($results)) {
                    foreach ($results as $path => $hash) {
                        if (strlen($hash) === 16 && isset($pathsToProducts[$path])) {
                            $pathsToProducts[$path]->update(['photo_hash' => $hash]);
                        }
                    }
                }
            }
            $bar->advance(count($chunk));
        }
        $bar->finish();
        $this->newLine();

        if ($missingFiles > 0) {
            $this->warn("Could not find local files for {$missingFiles} products.");
        }

        // 2. Find and merge duplicates
        $this->info('Merging duplicates...');
        
        // Get all group_id and photo_hash combinations that have duplicates
        $duplicates = DB::table('products')
            ->select('group_id', 'photo_hash', DB::raw('COUNT(*) as count'))
            ->whereNotNull('photo_hash')
            ->groupBy('group_id', 'photo_hash')
            ->having('count', '>', 1)
            ->get();

        $mergedCount = 0;
        $deletedCount = 0;

        foreach ($duplicates as $dup) {
            // Get all products for this group+hash, ordered by first_seen_at (oldest first)
            $dupeProducts = Product::where('group_id', $dup->group_id)
                ->where('photo_hash', $dup->photo_hash)
                ->orderBy('first_seen_at', 'asc')
                ->get();

            if ($dupeProducts->count() <= 1) continue;

            $primary = $dupeProducts->first();
            $others = $dupeProducts->slice(1);

            foreach ($others as $other) {
                // Move price history
                PriceHistory::where('product_id', $other->id)
                    ->update(['product_id' => $primary->id]);

                // Update primary if other is newer
                if ($other->last_updated_at > $primary->last_updated_at) {
                    $primary->update([
                        'latest_price' => $other->latest_price,
                        'latest_message_id' => $other->latest_message_id,
                        'last_updated_at' => $other->last_updated_at,
                    ]);
                }

                // Delete the duplicate
                $other->delete();
                $deletedCount++;
            }
            $mergedCount++;
        }

        $this->info("Deduplication complete. Merged {$mergedCount} groups of duplicates, deleted {$deletedCount} duplicate products.");
    }
}
