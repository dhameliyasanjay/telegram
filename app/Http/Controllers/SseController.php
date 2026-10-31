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
        // 1. Release session lock immediately so other requests from this user don't block
        if ($request->hasSession()) {
            $request->session()->save();
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // On PHP CLI built-in server (php artisan serve), it is single-threaded on Windows.
        // A long-lived loop blocks ALL other incoming requests (tabs, clicks, admin, login).
        // Under CLI server, we run a short burst (2-3 seconds max) and tell client to reconnect,
        // freeing the worker process immediately.
        $isCliServer = php_sapi_name() === 'cli-server';
        $maxIterations = $isCliServer ? 2 : 30;

        return response()->stream(function () use ($maxIterations) {
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

                // Break and tell client to reconnect
                $iteration++;
                if ($iteration >= $maxIterations) {
                    echo "event: reconnect\n";
                    echo "data: {}\n\n";
                    flush();
                    break;
                }

                sleep(1);
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }
}
