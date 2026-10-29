<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check admin guard first, then fallback to web guard
        $user = Auth::guard('admin')->user() ?? Auth::guard('web')->user();

        if (! $user) {
            return $request->expectsJson()
                ? response()->json(['error' => 'Unauthenticated.'], 401)
                : redirect()->guest(route('admin.login'));
        }

        // Check if user is blocked
        if ($user->isBlocked()) {
            Auth::guard('admin')->logout();

            return $request->expectsJson()
                ? response()->json(['error' => 'Your account is suspended.'], 403)
                : redirect()->route('admin.login')->withErrors(['email' => 'Your account has been suspended by an administrator.']);
        }

        // Strict verification: Must have admin role
        if (! $user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Forbidden. Administrator privileges required.'], 403);
            }

            return redirect()->route('admin.login')->withErrors([
                'email' => 'Access Restricted: You were signed in with a standard customer account. Please enter your Administrator credentials to access the Admin Panel.',
            ]);
        }

        // Set default auth guard to admin for this request lifecycle
        Auth::shouldUse('admin');

        return $next($request);
    }
}

