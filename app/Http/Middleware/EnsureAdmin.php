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
        if (! Auth::check()) {
            return $request->expectsJson()
                ? response()->json(['error' => 'Unauthenticated.'], 401)
                : redirect()->guest(route('admin.login'));
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check if user is blocked
        if ($user->isBlocked()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['error' => 'Your account is suspended.'], 403)
                : redirect()->route('admin.login')->withErrors(['email' => 'Your account has been suspended by an administrator.']);
        }

        // Check if user is an admin
        if (! $user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Forbidden. Administrator privileges required.'], 403);
            }

            // Cleanly log out standard user and redirect to Admin Login with clear guidance
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->withErrors([
                'email' => 'Access Restricted: You were signed in with a standard customer account. Please enter your Administrator credentials to access the Admin Panel.',
            ]);
        }

        return $next($request);
    }
}

