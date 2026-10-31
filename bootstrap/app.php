<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin'       => \App\Http\Middleware\EnsureAdmin::class,
            'not_blocked' => \App\Http\Middleware\CheckNotBlocked::class,
        ]);
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\CheckNotBlocked::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'webhooks/razorpay',
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Your security session has expired. Please refresh the page.',
                    'code'  => 419,
                ], 419);
            }

            if ($request->is('admin*')) {
                if (\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->isAdmin()) {
                    return back()->with('error', 'Your session security token refreshed. Please submit your changes again.');
                }

                return redirect()->route('admin.login')->withErrors([
                    'email' => 'Your session expired due to inactivity. Please sign in to continue.',
                ]);
            }

            if (\Illuminate\Support\Facades\Auth::check()) {
                return back()->with('error', 'Your session token refreshed. Please try again.');
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Your session expired. Please sign in to continue.',
            ]);
        });
    })->create();
