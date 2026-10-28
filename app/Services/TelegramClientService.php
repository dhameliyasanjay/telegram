<?php

namespace App\Services;

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use Illuminate\Support\Facades\Log;

class TelegramClientService
{
    private string $sessionPath;
    private int    $apiId;
    private string $apiHash;

    public function __construct()
    {
        $this->sessionPath = storage_path('app/telegram/session.madeline');
        $this->apiId       = (int) config('services.telegram.api_id');
        $this->apiHash     = config('services.telegram.api_hash');

        $dir = dirname($this->sessionPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function getSessionPath(): string
    {
        return $this->sessionPath;
    }

    public function getSettings(): Settings
    {
        $settings = new Settings();
        $settings->setAppInfo(
            (new AppInfo())
                ->setApiId($this->apiId)
                ->setApiHash($this->apiHash)
        );
        return $settings;
    }

    public function getClient(): API
    {
        return new API($this->getSessionPath(), $this->getSettings());
    }

    /**
     * Join a group by invite link or username. Silent if already a member.
     */
    public function joinGroup(API $client, string $link): void
    {
        try {
            if ($this->isInviteLink($link)) {
                $client->messages->importChatInvite(['hash' => $this->extractInviteHash($link)]);
            } else {
                $client->channels->joinChannel(['channel' => $this->extractUsername($link)]);
            }
        } catch (\Throwable $e) {
            Log::info('joinGroup (already member or error): ' . $e->getMessage());
        }
    }

    /**
     * Resolve a peer link and return normalized group info array.
     * Uses getPwrChat() (returns title/participants_count directly), with getInfo() fallback.
     */
    public function getGroupInfo(API $client, string $link): array
    {
        $peer = $this->isInviteLink($link)
            ? $this->extractInviteHash($link)
            : $this->extractUsername($link);

        // ── Primary: getPwrChat (cleanest structure) ──────────────────────
        try {
            $info = $client->getPwrChat($peer, false);
            Log::info('getPwrChat OK', ['id' => $info['id'] ?? null, 'title' => $info['title'] ?? null, 'type' => $info['type'] ?? null]);

            return [
                'telegram_id'  => abs((int)($info['id'] ?? 0)),
                'title'        => $info['title'] ?? $info['first_name'] ?? $peer,
                'invite_link'  => $link,
                'member_count' => (int)($info['participants_count'] ?? 0),
                'photo_url'    => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('getPwrChat failed: ' . $e->getMessage());
        }

        // ── Fallback: getInfo ─────────────────────────────────────────────
        // getInfo() returns: bot_api_id, type, Chat=>[...], User=>[...]
        // 'Chat' key covers both channels AND basic chats in MadelineProto v8
        try {
            $info      = $client->getInfo($peer);
            $botApiId  = (int)($info['bot_api_id'] ?? 0);
            $peerObj   = $info['Chat'] ?? $info['User'] ?? [];
            $title     = $peerObj['title'] ?? $peerObj['first_name'] ?? $peer;
            $members   = $peerObj['participants_count'] ?? 0;

            Log::info('getInfo fallback OK', ['bot_api_id' => $botApiId, 'title' => $title]);

            return [
                'telegram_id'  => abs($botApiId),
                'title'        => $title,
                'invite_link'  => $link,
                'member_count' => (int)$members,
                'photo_url'    => null,
            ];
        } catch (\Throwable $e) {
            Log::error('getInfo fallback failed: ' . $e->getMessage());
        }

        return ['telegram_id' => 0, 'title' => $peer, 'invite_link' => $link, 'member_count' => 0, 'photo_url' => null];
    }

    /**
     * Fetch recent messages from a group/channel, up to $limit.
     */
    public function getMessages(API $client, int|string $peer, int $limit = 100, int $offsetId = 0): array
    {
        try {
            $messages  = [];
            $batchSize = min($limit, 100);
            $fetched   = 0;

            while ($fetched < $limit) {
                try {
                    $batch = $client->messages->getHistory([
                        'peer'        => $peer,
                        'offset_id'   => $offsetId,
                        'offset_date' => 0,
                        'add_offset'  => 0,
                        'limit'       => $batchSize,
                        'max_id'      => 0,
                        'min_id'      => 0,
                        'hash'        => 0,
                    ]);
                } catch (\Throwable $histErr) {
                    if (preg_match('/FLOOD_WAIT_(\d+)/i', $histErr->getMessage(), $m)) {
                        $waitSec = (int) $m[1];
                        if ($waitSec <= 15) {
                            Log::warning("Telegram getHistory FLOOD_WAIT_{$waitSec}s. Sleeping and retrying once.");
                            sleep($waitSec + 1);
                            continue;
                        }
                    }
                    throw $histErr;
                }

                $msgs = $batch['messages'] ?? [];
                if (empty($msgs)) break;

                $messages = array_merge($messages, $msgs);
                $fetched += count($msgs);

                $offsetId = end($msgs)['id'];
            }

            return $messages;
        } catch (\Throwable $e) {
            Log::error('getMessages failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Download a photo from a message and return its public URL.
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

            // Verify file actually exists and has positive size
            if (!file_exists($localPath) || filesize($localPath) === 0) {
                if (file_exists($localPath)) {
                    @unlink($localPath);
                }
                return null;
            }

            return '/storage/' . $filename;
        } catch (\Throwable $e) {
            Log::warning('downloadPhoto failed: ' . $e->getMessage());
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function isInviteLink(string $link): bool
    {
        return str_contains($link, 'joinchat') || str_contains($link, '/+');
    }

    private function extractInviteHash(string $link): string
    {
        $hash = preg_replace('/.*\/\+/', '', $link);
        return preg_replace('/.*joinchat\//', '', $hash ?? '');
    }

    private function extractUsername(string $link): string
    {
        $username = preg_replace('#^https?://t\.me/#', '', $link);
        $username = preg_replace('#^t\.me/#', '', $username ?? '');
        return ltrim($username ?? '', '@');
    }
}
