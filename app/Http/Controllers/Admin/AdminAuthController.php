<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    /**
     * Show the dedicated Admin Login form.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    /**
     * Handle incoming administrator authentication request.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('email')) . '|admin|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Too many admin login attempts. Please try again in {$seconds} seconds.",
            ])->onlyInput('email');
        }

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors([
                'email' => 'These credentials do not match our administrator records.',
            ])->onlyInput('email');
        }

        $user = Auth::user();

        // Strict verification: Must have admin role
        if (! $user->isAdmin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors([
                'email' => 'Access Denied: This account is a regular customer account, not an Administrator.',
            ])->onlyInput('email');
        }

        // Verification: Must not be suspended
        if ($user->isBlocked()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Your administrator account has been suspended.',
            ])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        AdminActivityLog::record(
            'admin_login',
            "Administrator [{$user->email}] signed in via Admin Portal",
            $user
        );

        return redirect()->intended(route('admin.dashboard'))->with('success', "Welcome back, {$user->name}!");
    }

    /**
     * Log out of the Administrator Portal.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            AdminActivityLog::record(
                'admin_logout',
                "Administrator [{$user->email}] signed out",
                $user
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'You have been signed out of the Administrator Portal.');
    }
}

