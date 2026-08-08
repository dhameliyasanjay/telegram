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

            if (empty($info['telegram_id'])) {
                return response()->json(['error' => 'Could not resolve group info. Check the invite link.'], 422);
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

            // Initial sync — fetch last 100 messages
            $messages = $this->telegramClient->getMessages($client, (int)$info['telegram_id'], 100);
            $stats    = ['new' => 0, 'updated' => 0, 'skipped' => 0];

            foreach (array_reverse($messages) as $msg) {
                $parsed = $this->parser->parse($msg);
                if (!$parsed) continue;

                $result = $this->ingestor->ingest($group->id, $parsed, $client);
                $stats[$result['action']] = ($stats[$result['action']] ?? 0) + 1;
            }

            $group->update(['last_synced_at' => now()]);

            return response()->json([
                'group' => $group->fresh()->loadCount('products'),
                'sync'  => $stats,
                'message' => "Group added and synced: {$stats['new']} new products, {$stats['updated']} updated.",
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Add group failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/groups/{id}/sync
     * Manually re-fetch and ingest recent messages.
     */
    public function sync(Request $request, int $id): JsonResponse
    {
        $group = TelegramGroup::findOrFail($id);
        $limit = (int) $request->input('limit', 100);

        try {
            $client = $this->telegramClient->getClient();
            $client->start();

            $messages = $this->telegramClient->getMessages($client, (int)$group->telegram_id, $limit);
            $stats    = ['new' => 0, 'updated' => 0, 'skipped' => 0];

            foreach (array_reverse($messages) as $msg) {
                $parsed = $this->parser->parse($msg);
                if (!$parsed) continue;

                $result = $this->ingestor->ingest($group->id, $parsed, $client);
                $stats[$result['action']] = ($stats[$result['action']] ?? 0) + 1;
            }

            $group->update(['last_synced_at' => now()]);

            return response()->json([
                'group'   => $group->fresh()->loadCount('products'),
                'sync'    => $stats,
                'message' => "Sync complete: {$stats['new']} new, {$stats['updated']} updated, {$stats['skipped']} skipped.",
            ]);

        } catch (\Throwable $e) {
            Log::error("Sync failed for group {$id}: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
