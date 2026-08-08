<?php

namespace App\Services;

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Settings\Database\Memory;
use Illuminate\Support\Facades\Log;

class TelegramClientService
{
    private string $sessionPath;
    private int $apiId;
    private string $apiHash;

    public function __construct()
    {
        $this->sessionPath = storage_path('app/telegram/session.madeline');
        $this->apiId       = (int) config('services.telegram.api_id');
        $this->apiHash     = config('services.telegram.api_hash');

        // Ensure session directory exists
        $dir = dirname($this->sessionPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Get a fully-authenticated MadelineProto API instance.
     * This is synchronous-safe for CLI; for web contexts use sparingly.
     */
    public function getClient(): API
    {
        $settings = new Settings();
        $settings->setAppInfo(
            (new AppInfo())
                ->setApiId($this->apiId)
                ->setApiHash($this->apiHash)
        );

        return new API($this->sessionPath, $settings);
    }

    /**
     * Join a group by its invite link or username.
     * Returns the resolved peer info.
     */
    public function joinGroup(API $client, string $link): array
    {
        try {
            // Try joining via invite link (t.me/+hash format)
            if (str_contains($link, 'joinchat') || str_contains($link, '/+')) {
                $hash = preg_replace('/.*\/\+/', '', $link);
                $hash = preg_replace('/.*joinchat\//', '', $hash);
                $result = $client->importChatInvite(['hash' => $hash]);
                Log::info('Joined group via invite hash', ['hash' => $hash]);
                return $result;
            }

            // Try as username or t.me/username link
            $username = preg_replace('/.*t\.me\//', '', $link);
            $username = ltrim($username, '@');
            $result = $client->joinChannel(['channel' => $username]);
            Log::info('Joined channel by username', ['username' => $username]);
            return $result;
        } catch (\Throwable $e) {
            Log::warning('Join group failed (may already be member): ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Resolve a peer and return its info (title, member count, photo).
     */
    public function getGroupInfo(API $client, string $link): array
    {
        $peer = $this->resolvePeer($link);
        try {
            $info = $client->getFullChat($peer);
            return $this->normalizeGroupInfo($info, $link);
        } catch (\Throwable $e) {
            Log::error('getGroupInfo failed: ' . $e->getMessage());
            return ['title' => $link, 'telegram_id' => 0, 'member_count' => 0, 'photo_url' => null];
        }
    }

    /**
     * Fetch recent messages from a group, up to $limit.
     */
    public function getMessages(API $client, int|string $peer, int $limit = 100): array
    {
        try {
            $messages = [];
            $offsetId  = 0;
            $batchSize = min($limit, 100);
            $fetched   = 0;

            while ($fetched < $limit) {
                $batch = $client->messages->getHistory([
                    'peer'       => $peer,
                    'offset_id'  => $offsetId,
                    'offset_date'=> 0,
                    'add_offset' => 0,
                    'limit'      => $batchSize,
                    'max_id'     => 0,
                    'min_id'     => 0,
                    'hash'       => 0,
                ]);

                $msgs = $batch['messages'] ?? [];
                if (empty($msgs)) break;

                $messages = array_merge($messages, $msgs);
                $fetched  += count($msgs);

                if (count($msgs) < $batchSize) break;

                $offsetId = end($msgs)['id'];
            }

            return $messages;
        } catch (\Throwable $e) {
            Log::error('getMessages failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Download a photo/document from a message and store it locally.
     * Returns the public URL path or null.
     */
    public function downloadPhoto(API $client, array $message): ?string
    {
        try {
            $media = $message['media'] ?? null;
            if (!$media) return null;

            $type = $media['_'] ?? '';
            if (!in_array($type, ['messageMediaPhoto', 'messageMediaDocument'])) {
                return null;
            }

            $filename  = 'telegram/' . uniqid('photo_', true) . '.jpg';
            $localPath = storage_path('app/public/' . $filename);

            $dir = dirname($localPath);
            if (!is_dir($dir)) mkdir($dir, 0755, true);

            $client->downloadToFile($media, $localPath);

            return '/storage/' . $filename;
        } catch (\Throwable $e) {
            Log::warning('downloadPhoto failed: ' . $e->getMessage());
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function resolvePeer(string $link): string
    {
        if (str_contains($link, 'joinchat') || str_contains($link, '/+')) {
            $hash = preg_replace('/.*\/\+/', '', $link);
            return preg_replace('/.*joinchat\//', '', $hash);
        }
        $username = preg_replace('/.*t\.me\//', '', $link);
        return ltrim($username, '@');
    }

    private function normalizeGroupInfo(array $info, string $link): array
    {
        $chat = $info['full_chat'] ?? $info['chat'] ?? $info['channel'] ?? [];
        $chats = $info['chats'] ?? [];

        // Find the main chat object
        $mainChat = $chat;
        if (empty($mainChat) && !empty($chats)) {
            $mainChat = $chats[0] ?? [];
        }

        $telegramId  = $mainChat['id'] ?? 0;
        $title       = $mainChat['title'] ?? ($link);
        $memberCount = $chat['participants_count'] ?? $mainChat['participants_count'] ?? 0;

        return [
            'telegram_id'  => (int) abs($telegramId),
            'title'        => $title,
            'invite_link'  => $link,
            'member_count' => (int) $memberCount,
            'photo_url'    => null, // Photo download handled separately if needed
        ];
    }
}
