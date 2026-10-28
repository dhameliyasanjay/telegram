<?php

namespace Tests\Feature;

use App\Models\CanonicalProduct;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\TelegramGroup;
use App\Models\User;
use App\Services\ProductIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SearchAndComparisonTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProductIdentityService $identityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->identityService = app(ProductIdentityService::class);

        $this->user = User::create([
            'name'     => 'Test Auditor',
            'email'    => 'auditor@telegram.local',
            'password' => Hash::make('Secret123!'),
        ]);
    }

    public function test_stats_endpoint_returns_correct_metrics(): void
    {
        $groupA = TelegramGroup::create([
            'telegram_id'  => 101,
            'title'        => 'Group Alpha',
            'invite_link'  => 'https://t.me/alpha',
            'member_count' => 1200,
        ]);
        $groupB = TelegramGroup::create([
            'telegram_id'  => 102,
            'title'        => 'Group Beta',
            'invite_link'  => 'https://t.me/beta',
            'member_count' => 850,
        ]);

        $p1 = Product::create([
            'group_id'     => $groupA->id,
            'product_name' => 'Stainless Steel Bottle 1000ml',
            'product_key'  => 'stainless steel bottle 1000ml',
            'latest_price' => 140.00,
        ]);
        $this->identityService->resolveCanonicalProduct($p1);

        $p2 = Product::create([
            'group_id'     => $groupB->id,
            'product_name' => 'Stainless Steel Bottle 1000ml',
            'product_key'  => 'stainless steel bottle 1000ml',
            'latest_price' => 165.00,
        ]);
        $this->identityService->resolveCanonicalProduct($p2);

        $response = $this->actingAs($this->user)->getJson('/api/board/stats');

        $response->assertStatus(200);
        $response->assertJson([
            'total_groups'             => 2,
            'total_canonical_products' => 1,
            'total_offers'             => 2,
            'multi_group_products'     => 1,
            'with_price'               => 2,
        ]);
    }

    public function test_search_by_partial_keyword_brand_and_model(): void
    {
        $group = TelegramGroup::create([
            'telegram_id'  => 201,
            'title'        => 'Supplier Group 1',
            'invite_link'  => 'https://t.me/supplier1',
            'member_count' => 500,
        ]);

        $pA = Product::create([
            'group_id'     => $group->id,
            'product_name' => 'Kemei Hair Clipper KM-6630 Rechargeable',
            'product_key'  => 'kemei hair clipper km 6630',
            'latest_price' => 299.00,
        ]);
        $this->identityService->resolveCanonicalProduct($pA);

        $pB = Product::create([
            'group_id'     => $group->id,
            'product_name' => 'Wireless Bluetooth Earbuds Pro 5',
            'product_key'  => 'wireless bluetooth earbuds pro 5',
            'latest_price' => 199.00,
        ]);
        $this->identityService->resolveCanonicalProduct($pB);

        // 1. Search by brand "Kemei"
        $respBrand = $this->actingAs($this->user)->getJson('/api/board?search=Kemei');
        $respBrand->assertStatus(200);
        $respBrand->assertJsonCount(1, 'products');
        $this->assertEquals('Kemei Hair Clipper KM-6630 Rechargeable', $respBrand->json('products.0.product_name'));

        // 2. Search by model "KM-6630"
        $respModel = $this->actingAs($this->user)->getJson('/api/board?search=KM-6630');
        $respModel->assertStatus(200);
        $respModel->assertJsonCount(1, 'products');
        $this->assertEquals('KM-6630', $respModel->json('products.0.model'));

        // 3. Search by partial keyword "earbuds"
        $respKeyword = $this->actingAs($this->user)->getJson('/api/board?search=earbuds');
        $respKeyword->assertStatus(200);
        $respKeyword->assertJsonCount(1, 'products');
        $this->assertStringContainsString('Earbuds', $respKeyword->json('products.0.product_name'));
    }

    public function test_filter_by_group_id(): void
    {
        $groupA = TelegramGroup::create([
            'telegram_id'  => 301,
            'title'        => 'Surat Wholesalers',
            'invite_link'  => 'https://t.me/surat',
        ]);
        $groupB = TelegramGroup::create([
            'telegram_id'  => 302,
            'title'        => 'Mumbai Traders',
            'invite_link'  => 'https://t.me/mumbai',
        ]);

        $pA = Product::create([
            'group_id'     => $groupA->id,
            'product_name' => 'Product Only In Surat',
            'product_key'  => 'product only in surat',
            'latest_price' => 100.00,
        ]);
        $this->identityService->resolveCanonicalProduct($pA);

        $pB = Product::create([
            'group_id'     => $groupB->id,
            'product_name' => 'Product Only In Mumbai',
            'product_key'  => 'product only in mumbai',
            'latest_price' => 200.00,
        ]);
        $this->identityService->resolveCanonicalProduct($pB);

        $resp = $this->actingAs($this->user)->getJson("/api/board?group_id={$groupA->id}");
        $resp->assertStatus(200);
        $resp->assertJsonCount(1, 'products');
        $this->assertEquals('Product Only In Surat', $resp->json('products.0.product_name'));
    }

    public function test_filter_by_price_range(): void
    {
        $group = TelegramGroup::create([
            'telegram_id'  => 401,
            'title'        => 'Pricing Group',
            'invite_link'  => 'https://t.me/pricing',
        ]);

        $cheap = Product::create([
            'group_id'     => $group->id,
            'product_name' => 'Cheap Item',
            'product_key'  => 'cheap item',
            'latest_price' => 50.00,
        ]);
        $this->identityService->resolveCanonicalProduct($cheap);

        $mid = Product::create([
            'group_id'     => $group->id,
            'product_name' => 'Mid Item',
            'product_key'  => 'mid item',
            'latest_price' => 150.00,
        ]);
        $this->identityService->resolveCanonicalProduct($mid);

        $expensive = Product::create([
            'group_id'     => $group->id,
            'product_name' => 'Expensive Item',
            'product_key'  => 'expensive item',
            'latest_price' => 500.00,
        ]);
        $this->identityService->resolveCanonicalProduct($expensive);

        // Filter min_price=100 & max_price=200 -> only Mid Item
        $resp = $this->actingAs($this->user)->getJson('/api/board?min_price=100&max_price=200');
        $resp->assertStatus(200);
        $resp->assertJsonCount(1, 'products');
        $this->assertEquals('Mid Item', $resp->json('products.0.product_name'));
    }

    public function test_sorting_by_cheapest_and_highest_price(): void
    {
        $group = TelegramGroup::create([
            'telegram_id'  => 501,
            'title'        => 'Sort Test Group',
            'invite_link'  => 'https://t.me/sortgroup',
        ]);

        $p1 = Product::create([
            'group_id'     => $group->id,
            'product_name' => 'Alpha Product 300',
            'product_key'  => 'alpha product 300',
            'latest_price' => 300.00,
        ]);
        $this->identityService->resolveCanonicalProduct($p1);

        $p2 = Product::create([
            'group_id'     => $group->id,
            'product_name' => 'Beta Product 100',
            'product_key'  => 'beta product 100',
            'latest_price' => 100.00,
        ]);
        $this->identityService->resolveCanonicalProduct($p2);

        $p3 = Product::create([
            'group_id'     => $group->id,
            'product_name' => 'Gamma Product 200',
            'product_key'  => 'gamma product 200',
            'latest_price' => 200.00,
        ]);
        $this->identityService->resolveCanonicalProduct($p3);

        // Sort cheapest: 100 -> 200 -> 300
        $respCheapest = $this->actingAs($this->user)->getJson('/api/board?sort=cheapest');
        $respCheapest->assertStatus(200);
        $this->assertEquals(100.00, $respCheapest->json('products.0.cheapest_price'));
        $this->assertEquals(200.00, $respCheapest->json('products.1.cheapest_price'));
        $this->assertEquals(300.00, $respCheapest->json('products.2.cheapest_price'));

        // Sort highest: 300 -> 200 -> 100
        $respHighest = $this->actingAs($this->user)->getJson('/api/board?sort=highest');
        $respHighest->assertStatus(200);
        $this->assertEquals(300.00, $respHighest->json('products.0.cheapest_price'));
        $this->assertEquals(200.00, $respHighest->json('products.1.cheapest_price'));
        $this->assertEquals(100.00, $respHighest->json('products.2.cheapest_price'));
    }

    public function test_multi_group_price_comparison_rules_a_and_b(): void
    {
        // 3 distinct groups
        $groupA = TelegramGroup::create([
            'telegram_id'  => 601,
            'title'        => 'Supplier Group A',
            'invite_link'  => 'https://t.me/supplier_a',
            'member_count' => 1500,
        ]);
        $groupB = TelegramGroup::create([
            'telegram_id'  => 602,
            'title'        => 'Supplier Group B',
            'invite_link'  => 'https://t.me/supplier_b',
            'member_count' => 2200,
        ]);
        $groupC = TelegramGroup::create([
            'telegram_id'  => 603,
            'title'        => 'Supplier Group C',
            'invite_link'  => 'https://t.me/supplier_c',
            'member_count' => 800,
        ]);

        // Same product posted in all 3 groups with different prices:
        // Group A: ₹140 (Cheapest)
        // Group B: ₹155 (+₹15)
        // Group C: ₹165 (+₹25)
        $offerA = Product::create([
            'group_id'          => $groupA->id,
            'product_name'      => 'Stainless Steel Vacuum Flask 1000ml',
            'product_key'       => 'stainless steel vacuum flask 1000ml',
            'latest_price'      => 140.00,
            'latest_message_id' => 771,
        ]);
        $canonical = $this->identityService->resolveCanonicalProduct($offerA);

        $offerB = Product::create([
            'group_id'          => $groupB->id,
            'product_name'      => 'Stainless Steel Vacuum Flask 1000ml',
            'product_key'       => 'stainless steel vacuum flask 1000ml',
            'latest_price'      => 155.00,
            'latest_message_id' => 882,
        ]);
        $this->identityService->resolveCanonicalProduct($offerB);

        $offerC = Product::create([
            'group_id'          => $groupC->id,
            'product_name'      => 'Stainless Steel Vacuum Flask 1000ml',
            'product_key'       => 'stainless steel vacuum flask 1000ml',
            'latest_price'      => 165.00,
            'latest_message_id' => 993,
        ]);
        $this->identityService->resolveCanonicalProduct($offerC);

        // Query Board API
        $response = $this->actingAs($this->user)->getJson('/api/board?search=Vacuum Flask');
        $response->assertStatus(200);

        $items = $response->json('products');
        $this->assertCount(1, $items); // Exactly 1 canonical product card

        $product = $items[0];
        $this->assertEquals(140.00, $product['cheapest_price']);
        $this->assertEquals(165.00, $product['highest_price']);
        $this->assertEquals(3, $product['offers_count']);
        $this->assertEquals('Supplier Group A', $product['cheapest_group']['title']);

        // Verify Rule A & Rule B in offers comparison list
        $offers = $product['offers'];
        $this->assertCount(3, $offers); // Rule B: All 3 competing groups shown

        // Sorted by lowest price first:
        // 1st offer (Group A @ ₹140):
        $this->assertEquals('Supplier Group A', $offers[0]['group_title']);
        $this->assertEquals(140.00, $offers[0]['latest_price']);
        $this->assertTrue($offers[0]['is_cheapest']);
        $this->assertEquals(0.00, $offers[0]['price_diff']);
        $this->assertEquals('https://t.me/supplier_a/771', $offers[0]['group_link']);

        // 2nd offer (Group B @ ₹155):
        $this->assertEquals('Supplier Group B', $offers[1]['group_title']);
        $this->assertEquals(155.00, $offers[1]['latest_price']);
        $this->assertFalse($offers[1]['is_cheapest']);
        $this->assertEquals(15.00, $offers[1]['price_diff']); // +₹15 difference
        $this->assertEquals('https://t.me/supplier_b/882', $offers[1]['group_link']);

        // 3rd offer (Group C @ ₹165):
        $this->assertEquals('Supplier Group C', $offers[2]['group_title']);
        $this->assertEquals(165.00, $offers[2]['latest_price']);
        $this->assertFalse($offers[2]['is_cheapest']);
        $this->assertEquals(25.00, $offers[2]['price_diff']); // +₹25 difference
        $this->assertEquals('https://t.me/supplier_c/993', $offers[2]['group_link']);
    }

    public function test_canonical_product_show_endpoint(): void
    {
        $group = TelegramGroup::create([
            'telegram_id' => 701,
            'title'       => 'Direct Show Group',
            'invite_link' => 'https://t.me/directshow',
        ]);

        $prod = Product::create([
            'group_id'     => $group->id,
            'product_name' => 'Smart Watch Series 9 AMOLED',
            'product_key'  => 'smart watch series 9 amoled',
            'latest_price' => 799.00,
        ]);
        $canonical = $this->identityService->resolveCanonicalProduct($prod);

        $resp = $this->actingAs($this->user)->getJson("/api/canonical-products/{$canonical->id}");
        $resp->assertStatus(200);
        $resp->assertJsonPath('product.id', $canonical->id);
        $resp->assertJsonPath('product.product_name', 'Smart Watch Series 9 AMOLED');
        $resp->assertJsonPath('product.cheapest_price', 799);
        $resp->assertJsonCount(1, 'product.offers');
    }

    public function test_price_history_endpoint_returns_variations(): void
    {
        $group = TelegramGroup::create([
            'telegram_id' => 801,
            'title'       => 'History Group',
            'invite_link' => 'https://t.me/history',
        ]);

        $prod = Product::create([
            'group_id'     => $group->id,
            'product_name' => 'Mini Drone 4K Camera',
            'product_key'  => 'mini drone 4k camera',
            'latest_price' => 850.00,
        ]);

        PriceHistory::create([
            'product_id'   => $prod->id,
            'price'        => 950.00,
            'message_text' => 'Mini Drone 4K Camera rate 950',
            'posted_at'    => '2026-10-01 10:00:00',
            'recorded_at'  => '2026-10-01 10:00:00',
        ]);

        PriceHistory::create([
            'product_id'   => $prod->id,
            'price'        => 850.00,
            'message_text' => 'Mini Drone 4K Camera new rate 850',
            'posted_at'    => '2026-10-05 12:00:00',
            'recorded_at'  => '2026-10-05 12:00:00',
        ]);

        $resp = $this->actingAs($this->user)->getJson("/api/products/{$prod->id}/history");
        $resp->assertStatus(200);
        $resp->assertJsonCount(2, 'history');
        // Ordered by latest posted_at desc:
        $this->assertEquals(850.00, $resp->json('history.0.price'));
        $this->assertEquals(950.00, $resp->json('history.1.price'));
    }
}
