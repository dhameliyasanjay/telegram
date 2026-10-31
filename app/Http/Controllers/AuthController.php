<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        return view('auth.login');
    }

    /**
     * Handle an incoming login request.
     */
    public function login(Request $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => "Too many login attempts. Please try again in {$seconds} seconds.",
                ], 429);
            }
            return back()->withErrors([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ])->onlyInput('email');
        }

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            RateLimiter::hit($throttleKey, 60);

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'The provided credentials do not match our records.',
                ], 401);
            }

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Login successful',
                'user'    => Auth::user(),
            ]);
        }

        return redirect()->intended('/');
    }

    /**
     * Show the registration form.
     */
    public function showRegisterForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)],
        ]);

        $trialDays = (int) app(\App\Services\SettingService::class)->get('trial_days', 30);
        if ($trialDays <= 0) {
            $trialDays = 30;
        }

        $user = new User([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->role = 'user';
        $user->status = 'active';
        $user->subscription_status = 'trial';
        $user->trial_ends_at = now()->addDays($trialDays);
        $user->save();

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Registration successful',
                'user'    => $user,
            ], 201);
        }

        return redirect()->intended('/')->with('success', "Welcome, {$user->name}! Your {$trialDays}-day free trial has been activated.");
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse|JsonResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Logged out successfully',
            ]);
        }

        return redirect()->route('login')->with('status', 'You have been logged out.');
    }

    /**
     * Show the forgot password form.
     */
    public function showForgotPasswordForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        return view('auth.forgot-password');
    }

    /**
     * Send a password reset link to the given user.
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Show the reset password form.
     */
    public function showResetPasswordForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Reset the user's password.
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Show user profile and account settings.
     */
    public function showProfile(): View|JsonResponse
    {
        $user = Auth::user() ?? User::first();

        if (request()->wantsJson() || request()->is('api/*')) {
            $trialDaysLeft = 30;
            if ($user && $user->trial_ends_at) {
                $trialDaysLeft = max(0, (int) ceil(now()->diffInDays($user->trial_ends_at)));
            }
            return response()->json([
                'user'            => $user,
                'trial_days_left' => $trialDaysLeft,
            ]);
        }

        return view('auth.profile', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Update user profile settings.
     */
    public function updateProfile(Request $request): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user() ?? User::first();

        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'current_password' => ['nullable', 'required_with:new_password'],
            'new_password'     => ['nullable', 'string', 'confirmed', PasswordRule::min(8)],
        ]);

        if (!empty($validated['current_password']) && !Hash::check($validated['current_password'], $user->password)) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'The provided current password does not match.'], 422);
            }
            return back()->withErrors(['current_password' => 'The provided current password does not match.']);
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['new_password'])) {
            $user->password = Hash::make($validated['new_password']);
        }

        $user->save();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'user'    => $user,
            ]);
        }

        return back()->with('success', 'Profile updated successfully.');
    }
}

