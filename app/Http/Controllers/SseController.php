<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SseController extends Controller
{
    /**
     * GET /sse
     * Server-Sent Events endpoint — streams product updates to the frontend.
     */
    public function stream(Request $request)
    {
        return response()->stream(function () {
            // Send initial ping to establish connection
            echo "event: ping\n";
            echo "data: {\"ts\":\"" . now()->toIso8601String() . "\"}\n\n";
            flush();

            $lastEventTime = time();
            $iteration     = 0;

            while (true) {
                if (connection_aborted()) {
                    break;
                }

                // Check for queued SSE events
                $events = Cache::get('sse_events', []);
                if (!empty($events)) {
                    Cache::forget('sse_events');
                    foreach ($events as $event) {
                        $json = json_encode($event);
                        echo "event: product_update\n";
                        echo "data: {$json}\n\n";
                        flush();
                    }
                    $lastEventTime = time();
                }

                // Send a keepalive comment every 15 seconds to prevent proxy timeout
                if (time() - $lastEventTime >= 15) {
                    echo ": keepalive " . time() . "\n\n";
                    flush();
                    $lastEventTime = time();
                }

                // Break after 5 minutes — client will reconnect automatically
                $iteration++;
                if ($iteration >= 300) { // 300 × 1s = 5 min
                    echo "event: reconnect\n";
                    echo "data: {}\n\n";
                    flush();
                    break;
                }

                sleep(1);
            }
        }, 200, [
            'Content-Type'  => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',
            'Connection'    => 'keep-alive',
        ]);
    }
}
