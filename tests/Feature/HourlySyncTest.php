<?php

namespace Tests\Feature;

use App\Jobs\HourlyTelegramSyncJob;
use App\Models\CanonicalProduct;
use App\Models\Product;
use App\Models\TelegramGroup;
use App\Services\IngestMessageService;
use App\Services\MessageParserService;
use App\Services\ProductIdentityService;
use App\Services\TelegramClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HourlySyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_hourly_sync_command_dispatches_job_to_queue(): void
    {
        Queue::fake();

        $this->artisan('telegram:hourly-sync', ['--queue' => true, '--limit' => 50])
            ->assertExitCode(0);

        Queue::assertPushed(HourlyTelegramSyncJob::class, function ($job) {
            return $job->messageLimit === 50;
        });
    }

    public function test_hourly_sync_job_handles_empty_groups_gracefully(): void
    {
        $mockClient = $this->createMock(TelegramClientService::class);
        $mockParser = $this->createMock(MessageParserService::class);
        $mockIngest = $this->createMock(IngestMessageService::class);
        $mockIdentity = $this->createMock(ProductIdentityService::class);

        $job = new HourlyTelegramSyncJob(50);
        $job->handle($mockClient, $mockParser, $mockIngest, $mockIdentity);

        // Job completes without throwing exceptions when no groups exist
        $this->assertTrue(true);
    }

    public function test_hourly_sync_job_executes_workflow_for_monitored_groups(): void
    {
        $group = TelegramGroup::create([
            'telegram_id'  => 555123,
            'title'        => 'Hourly Test Group',
            'invite_link'  => 'https://t.me/hourlytest',
            'member_count' => 1500,
        ]);

        $mockClient = $this->createMock(TelegramClientService::class);
        $mockClient->method('getClient')->willReturn(null);
        $mockClient->method('isInviteLink')->willReturn(false);
        $mockClient->method('getMessages')->willReturn([
            [
                '_'       => 'message',
                'id'      => 801,
                'message' => "Ceramic Coffee Mug 350ml\nRate: ₹120/-",
                'date'    => time(),
            ]
        ]);

        $parser = app(MessageParserService::class);
        $ingestor = app(IngestMessageService::class);
        $identity = app(ProductIdentityService::class);

        $job = new HourlyTelegramSyncJob(10);
        $job->handle($mockClient, $parser, $ingestor, $identity);

        // Assert product was ingested
        $this->assertDatabaseHas('products', [
            'group_id'     => $group->id,
            'latest_price' => 120.00,
        ]);

        // Assert canonical product was created and linked
        $canonical = CanonicalProduct::first();
        $this->assertNotNull($canonical);
        $this->assertEquals(120.00, $canonical->cheapest_price);
        $this->assertEquals(1, $canonical->offers_count);

        // Assert group last_synced_at was updated
        $group->refresh();
        $this->assertNotNull($group->last_synced_at);
    }
}

