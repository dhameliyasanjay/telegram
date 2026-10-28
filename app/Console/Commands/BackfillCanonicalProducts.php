<?php

namespace App\Console\Commands;

use App\Models\CanonicalProduct;
use App\Models\Product;
use App\Models\TelegramGroup;
use App\Services\ProductIdentityService;
use Illuminate\Console\Command;

class BackfillCanonicalProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-canonical-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill canonical products from existing group-level product offers and deduplicate within groups';

    /**
     * Execute the console command.
     */
    public function handle(ProductIdentityService $identityService): int
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('  Telegram Products — Canonical Identity & Deduplication');
        $this->info('═══════════════════════════════════════════════════════════');

        $totalProducts = Product::count();
        $this->info("Scanning {$totalProducts} existing product offers across all groups...");

        $bar = $this->output->createProgressBar($totalProducts);
        $bar->start();

        Product::chunkById(250, function ($products) use ($identityService, $bar) {
            foreach ($products as $product) {
                if (!$product->canonical_product_id) {
                    $identityService->resolveCanonicalProduct($product, null, false);
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info('Running Rule A deduplication across individual groups (merging duplicate offers in same group)...');
        $groups = TelegramGroup::all();
        $totalMerged = 0;

        foreach ($groups as $group) {
            $merged = $identityService->deduplicateGroupOffers($group->id);
            if ($merged > 0) {
                $this->line("  ✓ Group [{$group->id}] {$group->title}: Merged {$merged} redundant duplicate offers");
                $totalMerged += $merged;
            }
        }

        $this->info('Refreshing aggregate stats on all canonical products in batch...');
        $identityService->refreshAllCanonicalStats();

        $canonicalCount = CanonicalProduct::count();
        $offerCount = Product::whereNotNull('canonical_product_id')->count();
        $multiGroupCount = CanonicalProduct::where('offers_count', '>', 1)->count();

        $this->newLine();
        $this->info("Backfill complete!");
        $this->info("  • Canonical Products Created : {$canonicalCount}");
        $this->info("  • Group Offers Mapped        : {$offerCount}");
        $this->info("  • Products in Multiple Groups: {$multiGroupCount}");
        $this->info("  • Redundant Within-Group Dupes Merged: {$totalMerged}");

        return self::SUCCESS;
    }
}
