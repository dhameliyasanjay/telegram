<?php

namespace App\Http\Controllers;

use App\Models\TelegramGroup;
use App\Services\IngestMessageService;
use App\Services\MessageParserService;
use App\Services\TelegramClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GroupController extends Controller
{
    public function __construct(
        private readonly TelegramClientService $telegramClient,
        private readonly MessageParserService  $parser,
        private readonly IngestMessageService  $ingestor,
    ) {}

    /**
     * GET /api/groups
     */
    public function index(): JsonResponse
    {
        $groups = TelegramGroup::withCount('products')->orderByDesc('added_at')->get();
        return response()->json(['groups' => $groups]);
    }

    /**
     * POST /api/groups
     * Body: { "link": "https://t.me/+XXXX" }
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate(['link' => 'required|string|min:5']);
        $link = trim($request->input('link'));

        try {
            $client = $this->telegramClient->getClient();
            $client->start();

            // Join the group
            $this->telegramClient->joinGroup($client, $link);

            // Fetch group metadata
            $info = $this->telegramClient->getGroupInfo($client, $link);

            Log::info('Group info resolved', $info);

            if (empty($info['telegram_id'])) {
                Log::error('Could not resolve telegram_id from group info', ['link' => $link, 'info' => $info]);
                return response()->json([
                    'error' => 'Could not resolve group info. Make sure: (1) you are logged in via php artisan telegram:login, (2) the link is valid (try the public username like https://t.me/supplyzonee), (3) your account can access this group.',
                    'debug' => $info,
                ], 422);
            }

            // Upsert the group record
            $group = TelegramGroup::updateOrCreate(
                ['telegram_id' => $info['telegram_id']],
                [
                    'title'        => $info['title'],
                    'invite_link'  => $link,
                    'member_count' => $info['member_count'],
                    'photo_url'    => $info['photo_url'],
                    'added_at'     => now(),
                ]
            );

            // Dispatch background job to sync recent 100 messages (prevents 504 timeouts on large channels)
            \App\Jobs\SyncTelegramGroup::dispatch($group, 100);

            return response()->json([
                'group'   => $group->fresh()->loadCount('products'),
                'sync'    => ['new' => 0, 'updated' => 0, 'skipped' => 0],
                'message' => "Group added! Syncing recent products in the background... they will appear shortly.",
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Add group failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/groups/sync-all
     * Manually dispatch background job to re-fetch and ingest recent messages for all groups.
     */
    public function syncAll(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 100);
        $full  = filter_var($request->input('full', true), FILTER_VALIDATE_BOOLEAN);
        $groups = TelegramGroup::all();
        $dispatched = 0;

        try {
            foreach ($groups as $group) {
                \App\Jobs\SyncTelegramGroup::dispatch($group, $full ? 200 : $limit, $full);
                $dispatched++;
            }

            return response()->json([
                'success' => true,
                'message' => $full 
                    ? "Full historical sync started in the background for $dispatched groups! This will crawl the entire history for all of them."
                    : "Sync started in background for $dispatched groups ($limit messages each). Watch the board for live updates!",
            ]);
        } catch (\Throwable $e) {
            Log::error("Sync-all failed: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/groups/{id}/sync
     * Manually dispatch background job to re-fetch and ingest recent messages.
     */
    public function sync(Request $request, int $id): JsonResponse
    {
        $group = TelegramGroup::findOrFail($id);
        $limit = (int) $request->input('limit', 100);
        $full  = (bool) $request->input('full', false);

        try {
            // If full=1, we fetch up to 200 per job, and the job will recursively dispatch itself
            \App\Jobs\SyncTelegramGroup::dispatch($group, $full ? 200 : $limit, $full);

            return response()->json([
                'group'   => $group->fresh()->loadCount('products'),
                'sync'    => ['new' => 0, 'updated' => 0, 'skipped' => 0],
                'message' => $full 
                    ? "Full historical sync started in the background. This will crawl the entire channel history!"
                    : "Sync started in background for {$limit} messages. Watch the board for live updates!",
            ]);

        } catch (\Throwable $e) {
            Log::error("Sync failed for group {$id}: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
