<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncTelegramGroup;
use App\Models\AdminActivityLog;
use App\Models\TelegramGroup;
use App\Services\TelegramClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminGroupController extends Controller
{
    public function __construct(
        private readonly TelegramClientService $telegramClient,
    ) {}

    /**
     * List all Telegram groups with search & status filters.
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = TelegramGroup::withCount('products');

        if ($request->filled('search')) {
            $term = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'LIKE', $term)
                  ->orWhere('invite_link', 'LIKE', $term)
                  ->orWhere('telegram_id', 'LIKE', $term);
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'paused') {
                $query->where('is_active', false);
            } elseif ($status === 'failed') {
                $query->where('sync_status', 'failed');
            } elseif ($status === 'syncing') {
                $query->where('sync_status', 'syncing');
            }
        }

        $sort = $request->input('sort', 'last_synced_at');
        $dir  = $request->input('dir', 'desc');

        if (in_array($sort, ['title', 'member_count', 'products_count', 'last_synced_at', 'added_at'])) {
            $query->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderByDesc('last_synced_at');
        }

        $groups = $query->paginate(15)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($groups);
        }

        return view('admin.groups.index', [
            'groups' => $groups,
            'filters' => $request->only(['search', 'status', 'sort', 'dir']),
        ]);
    }

    /**
     * Connect/Add a new Telegram group using existing MTProto integration.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'invite_link' => ['required', 'string', 'min:5'],
        ]);

        $link = trim($request->input('invite_link'));

        try {
            $client = $this->telegramClient->getClient();
            $client->start();

            // Join channel
            $this->telegramClient->joinGroup($client, $link);

            // Fetch metadata
            $info = $this->telegramClient->getGroupInfo($client, $link);

            if (empty($info['telegram_id'])) {
                $err = 'Could not resolve Telegram ID. Verify the link is valid and the account has access.';
                if ($request->wantsJson()) {
                    return response()->json(['error' => $err], 422);
                }
                return back()->withErrors(['invite_link' => $err])->withInput();
            }

            $group = TelegramGroup::updateOrCreate(
                ['telegram_id' => $info['telegram_id']],
                [
                    'title'        => $info['title'],
                    'invite_link'  => $link,
                    'member_count' => $info['member_count'],
                    'photo_url'    => $info['photo_url'],
                    'is_active'    => true,
                    'sync_status'  => 'idle',
                    'added_at'     => now(),
                ]
            );

            // Dispatch initial background sync
            SyncTelegramGroup::dispatch($group, 100);

            AdminActivityLog::record(
                'group_connect',
                "Connected Telegram group [{$group->title}] ({$group->telegram_id})",
                $group,
                ['link' => $link, 'member_count' => $info['member_count']]
            );

            $msg = "Group '{$group->title}' connected successfully. Background sync dispatched!";
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'group' => $group, 'message' => $msg], 201);
            }

            return redirect()->route('admin.groups.index')->with('success', $msg);

        } catch (\Throwable $e) {
            Log::error("Admin connect group failed: " . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return back()->withErrors(['invite_link' => 'Failed to connect group: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Toggle monitoring (pause / resume) for a group without deleting any product data.
     */
    public function toggleMonitoring(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $group = TelegramGroup::findOrFail($id);
        $group->is_active = !$group->is_active;
        $group->save();

        $action = $group->is_active ? 'group_resumed' : 'group_paused';
        $statusText = $group->is_active ? 'active (monitoring)' : 'paused (monitoring disabled)';

        AdminActivityLog::record(
            $action,
            "Set group [{$group->title}] status to {$statusText}",
            $group,
            ['is_active' => $group->is_active]
        );

        $msg = "Group '{$group->title}' is now {$statusText}.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'is_active' => $group->is_active, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Trigger manual background synchronization for a specific group.
     */
    public function sync(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $group = TelegramGroup::findOrFail($id);
        $limit = (int) $request->input('limit', 150);
        $full  = filter_var($request->input('full', false), FILTER_VALIDATE_BOOLEAN);

        SyncTelegramGroup::dispatch($group, $limit, $full);

        AdminActivityLog::record(
            'group_sync_triggered',
            "Triggered manual sync for [{$group->title}] (Limit: {$limit}, Full: " . ($full ? 'Yes' : 'No') . ")",
            $group
        );

        $msg = "Synchronization queued for '{$group->title}'. Live products will update shortly.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Trigger batch synchronization for all active groups.
     */
    public function syncAll(Request $request): RedirectResponse|JsonResponse
    {
        $groups = TelegramGroup::where('is_active', true)->get();
        $dispatched = 0;

        foreach ($groups as $group) {
            SyncTelegramGroup::dispatch($group, 100);
            $dispatched++;
        }

        AdminActivityLog::record(
            'batch_sync_triggered',
            "Triggered batch sync for {$dispatched} active groups"
        );

        $msg = "Batch synchronization queued for {$dispatched} monitored groups.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'dispatched' => $dispatched, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Update editable group properties (Title, Invite Link).
     */
    public function update(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $group = TelegramGroup::findOrFail($id);

        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'invite_link' => ['nullable', 'string', 'max:255'],
        ]);

        $old = ['title' => $group->title, 'invite_link' => $group->invite_link];
        $group->update($validated);

        AdminActivityLog::record(
            'group_updated',
            "Updated metadata for group [{$group->title}]",
            $group,
            ['old' => $old, 'new' => $validated]
        );

        $msg = "Group details updated successfully.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'group' => $group, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Safe group deactivation or deletion.
     */
    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $group = TelegramGroup::findOrFail($id);
        $hardDelete = filter_var($request->input('hard_delete', false), FILTER_VALIDATE_BOOLEAN);

        if (!$hardDelete) {
            // Safely deactivate monitoring
            $group->is_active = false;
            $group->save();

            AdminActivityLog::record(
                'group_deactivated',
                "Deactivated monitoring for group [{$group->title}] without deleting products",
                $group
            );

            $msg = "Group '{$group->title}' deactivated. Historical products and offers preserved.";
        } else {
            // Hard deletion requested
            $groupTitle = $group->title;
            AdminActivityLog::record(
                'group_deleted',
                "Removed group [{$groupTitle}] and decoupled associated offers",
                null,
                ['group_id' => $group->id, 'title' => $groupTitle]
            );

            // Nullify group_id on products to preserve historical products without foreign key constraint failures
            $group->products()->update(['group_id' => null]);
            $group->delete();

            $msg = "Group '{$groupTitle}' removed. Product offers preserved with unlinked group.";
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->route('admin.groups.index')->with('success', $msg);
    }
}

