<?php

namespace Tests\Feature;

use App\Jobs\SyncTelegramGroup;
use App\Models\CanonicalProduct;
use App\Models\Product;
use App\Models\TelegramGroup;
use App\Services\IngestMessageService;
use App\Services\MessageParserService;
use App\Services\TelegramClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TelegramPipelineTest extends TestCase
{
    use RefreshDatabase;

    private MessageParserService $parser;
    private IngestMessageService $ingestor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = app(MessageParserService::class);
        $this->ingestor = app(IngestMessageService::class);
    }

    public function test_sync_telegram_group_job_has_resilience_configuration(): void
    {
        $group = TelegramGroup::create([
            'telegram_id' => 100111222,
            'title'       => 'Config Test Group',
            'invite_link' => 'https://t.me/configtest',
        ]);

        $job = new SyncTelegramGroup($group, 100, false);

        $this->assertEquals(900, $job->timeout);
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(3, $job->maxExceptions);
        $this->assertEquals([30, 60, 180], $job->backoff());
    }

    public function test_telegram_sync_artisan_command_queues_all_groups(): void
    {
        Queue::fake();

        $g1 = TelegramGroup::create([
            'telegram_id' => 1001,
            'title'       => 'Wholesale Direct A',
            'invite_link' => 'https://t.me/direct_a',
        ]);
        $g2 = TelegramGroup::create([
            'telegram_id' => 1002,
            'title'       => 'Wholesale Direct B',
            'invite_link' => 'https://t.me/direct_b',
        ]);

        $this->artisan('telegram:sync', ['--queue' => true, '--limit' => 50])
            ->assertExitCode(0);

        Queue::assertPushed(SyncTelegramGroup::class, 2);
    }

    public function test_telegram_sync_artisan_command_filters_by_group_title(): void
    {
        Queue::fake();

        $g1 = TelegramGroup::create([
            'telegram_id' => 2001,
            'title'       => 'Surat Saree Wholesalers',
            'invite_link' => 'https://t.me/surat_saree',
        ]);
        $g2 = TelegramGroup::create([
            'telegram_id' => 2002,
            'title'       => 'Delhi Electronics Hub',
            'invite_link' => 'https://t.me/delhi_elec',
        ]);

        $this->artisan('telegram:sync', [
            '--group' => 'Surat',
            '--queue' => true,
        ])->assertExitCode(0);

        Queue::assertPushed(SyncTelegramGroup::class, function ($job) use ($g1) {
            return $job->group->id === $g1->id;
        });

        Queue::assertNotPushed(SyncTelegramGroup::class, function ($job) use ($g2) {
            return $job->group->id === $g2->id;
        });
    }

    public function test_sse_broadcast_payload_includes_canonical_product_id(): void
    {
        Cache::flush();

        $group = TelegramGroup::create([
            'telegram_id' => 3001,
            'title'       => 'SSE Test Group',
            'invite_link' => 'https://t.me/ssetest',
        ]);

        $parsed = [
            'product_key'  => 'stainless steel insulated flask 1000ml',
            'product_name' => 'Stainless Steel Insulated Flask 1000ml',
            'price'        => 220.00,
            'message_id'   => 551,
            'message_text' => 'Stainless Steel Insulated Flask 1000ml rate ₹220',
            'posted_at'    => '2026-10-28 12:00:00',
            'has_media'    => false,
        ];

        $res = $this->ingestor->ingest($group->id, $parsed);
        $this->assertEquals('new', $res['action']);

        $product = $res['product'];
        $this->assertNotNull($product->canonical_product_id);

        $events = Cache::get('sse_events', []);
        $this->assertNotEmpty($events);

        $lastEvent = end($events);
        $this->assertEquals('new', $lastEvent['action']);
        $this->assertEquals($product->id, $lastEvent['product']['id']);
        $this->assertEquals($product->canonical_product_id, $lastEvent['product']['canonical_product_id']);
        $this->assertEquals('SSE Test Group', $lastEvent['product']['group_title']);
    }

    public function test_message_parser_extracts_various_price_syntaxes(): void
    {
        // 1. ₹ symbol with comma
        $msg1 = [
            '_'       => 'message',
            'id'      => 10,
            'message' => "High Speed Blender 2000W\nPrice: ₹1,450/- only",
            'date'    => time(),
        ];
        $parsed1 = $this->parser->parse($msg1);
        $this->assertEquals(1450.00, $parsed1['price']);
        $this->assertEquals('High Speed Blender 2000W', $parsed1['product_name']);

        // 2. Trailing /- notation
        $msg2 = [
            '_'       => 'message',
            'id'      => 11,
            'message' => "Electric Hot Pot Cooker - 399/-",
            'date'    => time(),
        ];
        $parsed2 = $this->parser->parse($msg2);
        $this->assertEquals(399.00, $parsed2['price']);

        // 3. Rate: 250 notation
        $msg3 = [
            '_'       => 'message',
            'id'      => 12,
            'message' => "Mini Sealing Machine\nRate = 85 Rs",
            'date'    => time(),
        ];
        $parsed3 = $this->parser->parse($msg3);
        $this->assertEquals(85.00, $parsed3['price']);
    }

    public function test_message_parser_skips_non_messages(): void
    {
        $serviceMsg = [
            '_'       => 'messageService',
            'id'      => 99,
            'message' => 'User joined channel',
            'date'    => time(),
        ];
        $this->assertNull($this->parser->parse($serviceMsg));

        $emptyMsg = [
            '_'       => 'message',
            'id'      => 100,
            'message' => '',
            'date'    => time(),
        ];
        $this->assertNull($this->parser->parse($emptyMsg));
    }
}

