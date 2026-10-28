<?php

namespace Tests\Feature;

use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\TelegramGroup;
use App\Services\IngestMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngestMessageTest extends TestCase
{
    use RefreshDatabase;

    private IngestMessageService $ingestor;
    private TelegramGroup $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ingestor = app(IngestMessageService::class);
        $this->group = TelegramGroup::create([
            'telegram_id'  => 999123456,
            'title'        => 'Test Wholesaler Group',
            'invite_link'  => 'https://t.me/testwholesaler',
            'member_count' => 1200,
        ]);
    }

    public function test_new_product_is_created_with_initial_price_and_history(): void
    {
        $parsed = [
            'product_key'  => 'stainless steel water bottle 1000ml',
            'product_name' => 'Stainless Steel Water Bottle 1000ml',
            'price'        => 150.00,
            'message_id'   => 101,
            'message_text' => 'Stainless Steel Water Bottle 1000ml - ₹150',
            'posted_at'    => '2026-10-28 10:00:00',
            'has_media'    => false,
        ];

        $result = $this->ingestor->ingest($this->group->id, $parsed);

        $this->assertEquals('new', $result['action']);
        $this->assertDatabaseHas('products', [
            'group_id'     => $this->group->id,
            'product_key'  => 'stainless steel water bottle 1000ml',
            'latest_price' => 150.00,
        ]);

        $this->assertDatabaseHas('price_history', [
            'product_id' => $result['product']->id,
            'price'      => 150.00,
            'message_id' => 101,
        ]);
        $this->assertEquals(1, PriceHistory::where('product_id', $result['product']->id)->count());
    }

    public function test_reposting_same_product_at_same_price_does_not_duplicate_product_or_history(): void
    {
        $parsedDay1 = [
            'product_key'  => 'stainless steel water bottle 1000ml',
            'product_name' => 'Stainless Steel Water Bottle 1000ml',
            'price'        => 150.00,
            'message_id'   => 101,
            'message_text' => 'Stainless Steel Water Bottle 1000ml - ₹150',
            'posted_at'    => '2026-10-28 10:00:00',
            'has_media'    => false,
        ];
        $this->ingestor->ingest($this->group->id, $parsedDay1);

        // Day 2: Same product, same price
        $parsedDay2 = [
            'product_key'  => 'stainless steel water bottle 1000ml',
            'product_name' => 'Stainless Steel Water Bottle 1000ml',
            'price'        => 150.00,
            'message_id'   => 102,
            'message_text' => 'Stainless Steel Water Bottle 1000ml - ₹150 (In Stock)',
            'posted_at'    => '2026-10-29 10:00:00',
            'has_media'    => false,
        ];
        $result = $this->ingestor->ingest($this->group->id, $parsedDay2);

        $this->assertEquals('skipped', $result['action']);
        $this->assertEquals(1, Product::where('group_id', $this->group->id)->count());
        $this->assertEquals(1, PriceHistory::where('product_id', $result['product']->id)->count());
        $this->assertEquals(102, $result['product']->fresh()->latest_message_id);
    }

    public function test_valid_price_change_updates_offer_and_records_price_history(): void
    {
        $parsedDay1 = [
            'product_key'  => 'stainless steel water bottle 1000ml',
            'product_name' => 'Stainless Steel Water Bottle 1000ml',
            'price'        => 150.00,
            'message_id'   => 101,
            'message_text' => 'Stainless Steel Water Bottle 1000ml - ₹150',
            'posted_at'    => '2026-10-28 10:00:00',
            'has_media'    => false,
        ];
        $this->ingestor->ingest($this->group->id, $parsedDay1);

        // Day 3: Price drops to 135
        $parsedDay3 = [
            'product_key'  => 'stainless steel water bottle 1000ml',
            'product_name' => 'Stainless Steel Water Bottle 1000ml',
            'price'        => 135.00,
            'message_id'   => 103,
            'message_text' => 'Stainless Steel Water Bottle 1000ml - Price drop! Rate ₹135',
            'posted_at'    => '2026-10-30 10:00:00',
            'has_media'    => false,
        ];
        $result = $this->ingestor->ingest($this->group->id, $parsedDay3);

        $this->assertEquals('updated', $result['action']);
        $this->assertEquals(135.00, $result['product']->fresh()->latest_price);
        $this->assertEquals(2, PriceHistory::where('product_id', $result['product']->id)->count());
    }

    public function test_post_without_valid_price_does_not_destroy_last_known_valid_price(): void
    {
        $parsedDay1 = [
            'product_key'  => 'stainless steel water bottle 1000ml',
            'product_name' => 'Stainless Steel Water Bottle 1000ml',
            'price'        => 150.00,
            'message_id'   => 101,
            'message_text' => 'Stainless Steel Water Bottle 1000ml - ₹150',
            'posted_at'    => '2026-10-28 10:00:00',
            'has_media'    => false,
        ];
        $this->ingestor->ingest($this->group->id, $parsedDay1);

        // Subsequent post with no price (e.g. promotional repost without price)
        $parsedNoPrice = [
            'product_key'  => 'stainless steel water bottle 1000ml',
            'product_name' => 'Stainless Steel Water Bottle 1000ml',
            'price'        => null,
            'message_id'   => 104,
            'message_text' => 'Stainless Steel Water Bottle 1000ml - DM for bulk enquiry',
            'posted_at'    => '2026-10-31 10:00:00',
            'has_media'    => false,
        ];
        $result = $this->ingestor->ingest($this->group->id, $parsedNoPrice);

        $this->assertEquals('skipped', $result['action']);
        // Crucial requirement: last known price MUST still be 150.00!
        $this->assertEquals(150.00, $result['product']->fresh()->latest_price);
        $this->assertEquals(1, PriceHistory::where('product_id', $result['product']->id)->count());
    }
}

