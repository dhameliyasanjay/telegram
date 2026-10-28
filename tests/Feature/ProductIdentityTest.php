<?php

namespace Tests\Feature;

use App\Models\CanonicalProduct;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\TelegramGroup;
use App\Services\IngestMessageService;
use App\Services\ProductIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ProductIdentityTest extends TestCase
{
    use RefreshDatabase;

    private IngestMessageService $ingestor;
    private ProductIdentityService $identityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->identityService = app(ProductIdentityService::class);
        $this->ingestor = app(IngestMessageService::class);
    }

    public function test_product_posted_10_times_in_same_group_produces_one_current_offer(): void
    {
        $group = TelegramGroup::create([
            'telegram_id'  => 1001,
            'title'        => 'Supplier Group A',
            'invite_link'  => 'https://t.me/group_a',
            'member_count' => 500,
        ]);

        // Post 1 to 10 across days with prices changing
        for ($i = 1; $i <= 10; $i++) {
            $price = ($i <= 5) ? 150.00 : (($i <= 8) ? 140.00 : 135.00);
            $parsed = [
                'product_key'  => 'electric fascia massage ring',
                'product_name' => 'Electric Fascia Massage Ring',
                'price'        => $price,
                'message_id'   => 100 + $i,
                'message_text' => "Electric Fascia Massage Ring - ₹{$price}",
                'posted_at'    => "2026-10-0{$i} 10:00:00",
                'has_media'    => false,
            ];
            $this->ingestor->ingest($group->id, $parsed);
        }

        // Rule A: Exactly 1 current offer in Group A
        $this->assertEquals(1, Product::where('group_id', $group->id)->count());

        $offer = Product::where('group_id', $group->id)->first();
        $this->assertEquals(135.00, $offer->latest_price);

        // Price history recorded the 3 distinct price points: 150 -> 140 -> 135
        $this->assertEquals(3, PriceHistory::where('product_id', $offer->id)->count());

        // Canonical Product should have 1 offer
        $canonical = CanonicalProduct::first();
        $this->assertNotNull($canonical);
        $this->assertEquals(1, $canonical->offers_count);
        $this->assertEquals(135.00, $canonical->cheapest_price);
    }

    public function test_same_product_in_10_different_groups_produces_10_group_offers_under_one_canonical_product(): void
    {
        $groups = [];
        for ($g = 1; $g <= 10; $g++) {
            $groups[] = TelegramGroup::create([
                'telegram_id'  => 2000 + $g,
                'title'        => "Wholesaler Group {$g}",
                'invite_link'  => "https://t.me/group_{$g}",
                'member_count' => 1000 + ($g * 100),
            ]);
        }

        // Prices: Group 1=₹140, Group 2=₹155, Group 3=₹130 (cheapest!), etc.
        $prices = [140, 155, 130, 160, 145, 150, 138, 142, 170, 135];

        foreach ($groups as $idx => $grp) {
            $parsed = [
                'product_key'  => 'stainless steel water bottle 1000ml',
                'product_name' => 'Stainless Steel Water Bottle 1000ml',
                'price'        => (float) $prices[$idx],
                'message_id'   => 500 + $idx,
                'message_text' => "Stainless Steel Water Bottle 1000ml - ₹{$prices[$idx]}",
                'posted_at'    => '2026-10-28 10:00:00',
                'has_media'    => false,
            ];
            $this->ingestor->ingest($grp->id, $parsed);
        }

        // Rule B: Exactly 1 Canonical Product, but 10 separate group offers
        $this->assertEquals(1, CanonicalProduct::count());
        $this->assertEquals(10, Product::count());

        $canonical = CanonicalProduct::first();
        $this->assertEquals(10, $canonical->offers_count);

        // Cheapest known price should be ₹130 offered by Group 3 (index 2)
        $this->assertEquals(130.00, $canonical->cheapest_price);
        $this->assertEquals($groups[2]->id, $canonical->cheapest_group_id);
        $this->assertEquals(170.00, $canonical->highest_price);

        // Rule B: Every group has its own distinct offer
        foreach ($groups as $idx => $grp) {
            $offer = Product::where('group_id', $grp->id)->first();
            $this->assertNotNull($offer);
            $this->assertEquals((float) $prices[$idx], $offer->latest_price);
            $this->assertEquals($canonical->id, $offer->canonical_product_id);
        }
    }

    public function test_different_product_variants_are_not_incorrectly_merged(): void
    {
        $group = TelegramGroup::create([
            'telegram_id'  => 3001,
            'title'        => 'Variant Test Group',
            'invite_link'  => 'https://t.me/variant_group',
            'member_count' => 800,
        ]);

        // Variant 1: 1000ml bottle
        $this->ingestor->ingest($group->id, [
            'product_key'  => 'stainless steel water bottle 1000ml',
            'product_name' => 'Stainless Steel Water Bottle 1000ml',
            'price'        => 150.00,
            'message_id'   => 1,
            'message_text' => 'Stainless Steel Water Bottle 1000ml - ₹150',
            'posted_at'    => '2026-10-28 10:00:00',
            'has_media'    => false,
        ]);

        // Variant 2: 500ml bottle
        $this->ingestor->ingest($group->id, [
            'product_key'  => 'stainless steel water bottle 500ml',
            'product_name' => 'Stainless Steel Water Bottle 500ml',
            'price'        => 90.00,
            'message_id'   => 2,
            'message_text' => 'Stainless Steel Water Bottle 500ml - ₹90',
            'posted_at'    => '2026-10-28 10:00:00',
            'has_media'    => false,
        ]);

        // Variant 3: Derma Roller 1.0mm
        $this->ingestor->ingest($group->id, [
            'product_key'  => 'derma roller 1 0 mm',
            'product_name' => 'Derma Roller 1.0 MM',
            'price'        => 65.00,
            'message_id'   => 3,
            'message_text' => 'Derma Roller 1.0 MM - ₹65',
            'posted_at'    => '2026-10-28 10:00:00',
            'has_media'    => false,
        ]);

        // Variant 4: Derma Roller 0.5mm
        $this->ingestor->ingest($group->id, [
            'product_key'  => 'derma roller 0 5 mm',
            'product_name' => 'Derma Roller 0.5 MM',
            'price'        => 60.00,
            'message_id'   => 4,
            'message_text' => 'Derma Roller 0.5 MM - ₹60',
            'posted_at'    => '2026-10-28 10:00:00',
            'has_media'    => false,
        ]);

        // All 4 distinct variants must have their own Canonical Products
        $this->assertEquals(4, CanonicalProduct::count());
        $this->assertEquals(4, Product::count());
    }

    public function test_promotional_slogans_are_cleaned_from_canonical_product_name(): void
    {
        $group = TelegramGroup::create([
            'telegram_id'  => 4001,
            'title'        => 'Wholesale Bazar',
            'invite_link'  => 'https://t.me/wsbazar',
            'member_count' => 1500,
        ]);

        $rawText = "🔥🔥IN STOCK -- BEST RATE🔥🔥\nAluminium Fruit Juicer\n🔹Hand Press Citrus Press\nRATE 249/-\nCall and message 9909334000";

        $parsed = [
            'product_key'  => 'aluminium fruit juicer',
            'product_name' => 'Aluminium Fruit Juicer',
            'price'        => 249.00,
            'message_id'   => 777,
            'message_text' => $rawText,
            'posted_at'    => '2026-10-28 12:00:00',
            'has_media'    => false,
        ];

        $this->ingestor->ingest($group->id, $parsed);

        $canonical = CanonicalProduct::first();
        $this->assertNotNull($canonical);
        $this->assertStringNotContainsString('IN STOCK', $canonical->name);
        $this->assertStringNotContainsString('BEST RATE', $canonical->name);
        $this->assertStringContainsString('Aluminium Fruit Juicer', $canonical->name);
    }
}

