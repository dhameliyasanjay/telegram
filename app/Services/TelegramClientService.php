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
        $this->apiId       = (int) config('services.telegram.api_id', 0);
        $this->apiHash     = (string) (config('services.telegram.api_hash') ?? '');

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

    public function getClient(): ?API
    {
        return new API($this->getSessionPath(), $this->getSettings());
    }

    /**
     * Start client, join group, and retrieve group info in one call.
     */
    public function connectAndFetchInfo(string $link): array
    {
        $client = $this->getClient();
        $client->start();
        $this->joinGroup($client, $link);
        return $this->getGroupInfo($client, $link);
    }

    /**
     * Join a group by invite link or username. Throws descriptive error if invite is expired/invalid.
     */
    public function joinGroup(API $client, string $link): void
    {
        try {
            if ($this->isInviteLink($link)) {
                $hash = $this->extractInviteHash($link);
                $client->messages->importChatInvite(['hash' => $hash]);
            } else {
                $client->channels->joinChannel(['channel' => $this->extractUsername($link)]);
            }
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'USER_ALREADY_PARTICIPANT')) {
                // Already a member, perfectly normal
                return;
            }
            if (str_contains($msg, 'INVITE_HASH_EXPIRED')) {
                throw new \RuntimeException('The Telegram invite link has expired (INVITE_HASH_EXPIRED). Please ask the group admin for an active invite link.');
            }
            if (str_contains($msg, 'INVITE_HASH_INVALID')) {
                throw new \RuntimeException('The Telegram invite link is invalid. Please verify the URL.');
            }
            if (str_contains($msg, 'CHANNELS_TOO_MUCH')) {
                throw new \RuntimeException('The Telegram account has joined too many channels.');
            }
            Log::info('joinGroup notice: ' . $msg);
        }
    }

    /**
     * Resolve a peer link and return normalized group info array.
     * Uses checkChatInvite / getPwrChat / getInfo.
     */
    public function getGroupInfo(API $client, string $link): array
    {
        // ── 1. Handle Private Invite Links (t.me/+hash or t.me/joinchat/hash) ──
        if ($this->isInviteLink($link)) {
            $hash = $this->extractInviteHash($link);
            try {
                $check = $client->messages->checkChatInvite(['hash' => $hash]);
                $chat = $check['chat'] ?? $check;

                $rawId = abs((int)($chat['id'] ?? 0));
                // Convert channel ID to Bot API ID (-100...) format if needed
                $tid = $rawId;
                if ($rawId > 0 && !str_starts_with((string)$rawId, '100')) {
                    $tid = (int) ('100' . $rawId);
                }

                $title = $chat['title'] ?? $hash;
                $members = (int)($chat['participants_count'] ?? 0);

                if ($tid > 0) {
                    return [
                        'telegram_id'  => $tid,
                        'title'        => $title,
                        'invite_link'  => $link,
                        'member_count' => $members,
                        'photo_url'    => null,
                    ];
                }
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                if (str_contains($msg, 'INVITE_HASH_EXPIRED')) {
                    throw new \RuntimeException('The Telegram invite link has expired (INVITE_HASH_EXPIRED). Please ask the group admin for an active invite link.');
                }
                if (str_contains($msg, 'INVITE_HASH_INVALID')) {
                    throw new \RuntimeException('The Telegram invite link is invalid. Please verify the invite link.');
                }
                Log::warning('Invite check failed: ' . $msg);
            }
        }

        // ── 2. Handle Public Channels / Usernames ─────────────────────────────
        $peer = $this->isInviteLink($link)
            ? $this->extractInviteHash($link)
            : $this->extractUsername($link);

        // Primary: getPwrChat
        try {
            $info = $client->getPwrChat($peer, false);
            Log::info('getPwrChat OK', ['id' => $info['id'] ?? null, 'title' => $info['title'] ?? null]);

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

        // Fallback: getInfo
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
     * Uses MadelineProto when available; falls back to fast public channel crawler.
     */
    public function getMessages(?API $client, int|string $peer, int $limit = 100, int $offsetId = 0): array
    {
        // 1. Try MadelineProto if client is available and started
        if ($client) {
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

                if (!empty($messages)) {
                    return $messages;
                }
            } catch (\Throwable $e) {
                Log::warning('MTProto getMessages fallback triggered: ' . $e->getMessage());
            }
        }

        // 2. High-speed Web Channel Crawler Fallback (works without MTProto session)
        return $this->fetchPublicChannelMessages((string) $peer, $limit, $offsetId);
    }

    /**
     * Fetch recent messages directly from Telegram public web view (t.me/s/{username}).
     * Fast (< 300ms), reliable, and works without MTProto session keys.
     */
    public function fetchPublicChannelMessages(string $peer, int $limit = 100, int $offsetId = 0): array
    {
        $username = $this->extractUsername($peer);
        if (empty($username) || $this->isInviteLink($peer)) {
            return [];
        }

        $messages = [];
        $fetched = 0;
        $before = $offsetId > 0 ? $offsetId : null;
        $maxPages = (int) min(10, max(1, ceil($limit / 20)));

        for ($page = 0; $page < $maxPages && $fetched < $limit; $page++) {
            $url = "https://t.me/s/{$username}";
            if ($before) {
                $url .= "?before=" . $before;
            }

            $context = stream_context_create([
                'http' => [
                    'timeout' => 8,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    'header' => "Accept-Language: en-US,en;q=0.9\r\n",
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]);

            $html = @file_get_contents($url, false, $context);
            if (!$html) {
                break;
            }

            $prevErrors = libxml_use_internal_errors(true);
            $doc = new \DOMDocument();
            $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();
            libxml_use_internal_errors($prevErrors);
            $xpath = new \DOMXPath($doc);

            $wraps = $xpath->query("//div[contains(@class, 'tgme_widget_message_wrap')]");
            if (!$wraps || $wraps->length === 0) {
                break;
            }

            $batch = [];
            $minIdInBatch = null;

            foreach ($wraps as $wrap) {
                $msgDiv = $xpath->query(".//div[contains(@class, 'tgme_widget_message')]", $wrap)->item(0);
                if (!$msgDiv) continue;

                $dataPost = $msgDiv->getAttribute('data-post');
                $parts = explode('/', $dataPost);
                $msgId = isset($parts[1]) ? (int) $parts[1] : 0;
                if ($msgId <= 0) continue;

                if ($minIdInBatch === null || $msgId < $minIdInBatch) {
                    $minIdInBatch = $msgId;
                }

                // Text Content
                $textNode = $xpath->query(".//div[contains(@class, 'tgme_widget_message_text')]", $wrap)->item(0);
                $text = '';
                if ($textNode) {
                    foreach ($textNode->childNodes as $child) {
                        if ($child->nodeName === 'br') {
                            $text .= "\n";
                        } else {
                            $text .= $child->textContent;
                        }
                    }
                    $text = trim($text);
                }

                // Photo URL
                $photoNode = $xpath->query(".//a[contains(@class, 'tgme_widget_message_photo_wrap')]", $wrap)->item(0);
                $photoUrl = null;
                if ($photoNode) {
                    $style = $photoNode->getAttribute('style');
                    if (preg_match("/background-image:url\('([^']+)'\)/", $style, $m)) {
                        $photoUrl = $m[1];
                    }
                }

                // Timestamp
                $timeNode = $xpath->query(".//time", $wrap)->item(0);
                $date = $timeNode && $timeNode->getAttribute('datetime')
                    ? strtotime($timeNode->getAttribute('datetime'))
                    : time();

                if (!empty($text) || !empty($photoUrl)) {
                    $batch[] = [
                        '_'         => 'message',
                        'id'        => $msgId,
                        'message'   => $text,
                        'date'      => $date,
                        'media'     => !empty($photoUrl) ? ['_' => 'messageMediaPhoto'] : ['_' => 'messageMediaEmpty'],
                        'photo_url' => $photoUrl,
                    ];
                }
            }

            if (empty($batch)) {
                break;
            }

            foreach ($batch as $m) {
                $messages[] = $m;
                $fetched++;
                if ($fetched >= $limit) break;
            }

            if (!$minIdInBatch || $minIdInBatch === $before) {
                break;
            }
            $before = $minIdInBatch;
        }

        return $messages;
    }

    /**
     * Download a photo from a message and return its public URL.
     * Supports direct Telegram CDN URLs as well as MTProto downloads.
     */
    public function downloadPhoto(?API $client, array $message): ?string
    {
        try {
            // 1. Direct photo URL available (from public web crawler)
            if (!empty($message['photo_url']) && filter_var($message['photo_url'], FILTER_VALIDATE_URL)) {
                $filename  = 'telegram/' . uniqid('photo_', true) . '.jpg';
                $localPath = storage_path('app/public/' . $filename);
                $dir = dirname($localPath);
                if (!is_dir($dir)) mkdir($dir, 0755, true);

                $context = stream_context_create([
                    'http' => [
                        'timeout' => 8,
                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ]
                ]);

                $imgData = @file_get_contents($message['photo_url'], false, $context);
                if ($imgData !== false && strlen($imgData) > 0) {
                    file_put_contents($localPath, $imgData);
                    return '/storage/' . $filename;
                }
                return $message['photo_url'];
            }

            // 2. MTProto download if client available
            if ($client && !empty($message['media'])) {
                $media = $message['media'];
                $type = $media['_'] ?? '';
                if (!in_array($type, ['messageMediaPhoto', 'messageMediaDocument'])) {
                    return null;
                }

                $filename  = 'telegram/' . uniqid('photo_', true) . '.jpg';
                $localPath = storage_path('app/public/' . $filename);
                $dir = dirname($localPath);
                if (!is_dir($dir)) mkdir($dir, 0755, true);

                $client->downloadToFile($media, $localPath);

                if (!file_exists($localPath) || filesize($localPath) === 0) {
                    if (file_exists($localPath)) {
                        @unlink($localPath);
                    }
                    return null;
                }

                return '/storage/' . $filename;
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning('downloadPhoto failed: ' . $e->getMessage());
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fast, non-blocking metadata extraction using public Telegram web preview.
     * Takes < 100ms and never requires MadelineProto IPC locks.
     */
    public function fetchFastMetadata(string $link): array
    {
        $link = trim($link);
        $title = null;
        $image = null;
        $description = null;
        $username = $this->extractUsername($link);

        if (!empty($username) && !$this->isInviteLink($link)) {
            $url = 'https://t.me/' . ltrim($username, '@');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            $html = curl_exec($ch);
            curl_close($ch);

            if ($html) {
                if (preg_match('/<meta property="og:title" content="([^"]+)"/i', $html, $m)) {
                    $rawTitle = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
                    if (!str_contains($rawTitle, 'Join group chat on Telegram') && !str_contains($rawTitle, 'Telegram: Contact')) {
                        $title = $rawTitle;
                    }
                }
                if (preg_match('/<meta property="og:image" content="([^"]+)"/i', $html, $m)) {
                    $image = $m[1];
                }
                if (preg_match('/<meta property="og:description" content="([^"]+)"/i', $html, $m)) {
                    $description = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
                }
            }
        }

        if (empty($title)) {
            $cleanUser = ltrim($username ?? '', '@');
            $title = !empty($cleanUser) ? ucwords(str_replace(['_', '-'], ' ', $cleanUser)) : 'Telegram Channel';
        }

        return [
            'title'        => $title,
            'photo_url'    => $image,
            'description'  => $description,
            'invite_link'  => $link,
            'member_count' => 0,
        ];
    }

    public function isInviteLink(string $link): bool
    {
        return str_contains($link, 'joinchat') || str_contains($link, '/+');
    }

    public function extractInviteHash(string $link): string
    {
        $hash = preg_replace('/.*\/\+/', '', $link);
        return preg_replace('/.*joinchat\//', '', $hash ?? '');
    }

    public function extractUsername(string $link): string
    {
        $username = preg_replace('#^https?://t\.me/#', '', $link);
        $username = preg_replace('#^t\.me/#', '', $username ?? '');
        return ltrim($username ?? '', '@');
    }
}
