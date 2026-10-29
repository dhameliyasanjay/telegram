<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    /**
     * List users with search and filtering.
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = User::query();

        if ($request->filled('search')) {
            $term = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', $term)
                  ->orWhere('email', 'LIKE', $term);
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('subscription_status')) {
            $query->where('subscription_status', $request->input('subscription_status'));
        }

        $users = $query->orderByDesc('id')->paginate(20)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($users);
        }

        return view('admin.users.index', [
            'users'   => $users,
            'filters' => $request->only(['search', 'role', 'status', 'subscription_status']),
        ]);
    }

    /**
     * Toggle user status (active / blocked).
     */
    public function toggleStatus(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $user = User::findOrFail($id);

        // Security check: cannot block self
        if ($user->id === Auth::id()) {
            $err = 'You cannot block your own administrator account.';
            if ($request->wantsJson()) {
                return response()->json(['error' => $err], 400);
            }
            return back()->withErrors(['status' => $err]);
        }

        $user->status = $user->status === 'blocked' ? 'active' : 'blocked';
        $user->save();

        AdminActivityLog::record(
            'user_status_changed',
            "Set user [{$user->email}] status to {$user->status}",
            $user,
            ['status' => $user->status]
        );

        $msg = "User [{$user->email}] has been " . ($user->status === 'blocked' ? 'blocked and logged out.' : 'unblocked.');

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'status' => $user->status, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Toggle or update user role (admin vs user). Strictly two roles.
     */
    public function updateRole(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $user = User::findOrFail($id);

        $request->validate([
            'role' => ['required', Rule::in(['admin', 'user'])],
        ]);

        $newRole = $request->input('role');

        // Security check: prevent self-demotion if the admin is demoting themself
        if ($user->id === Auth::id() && $newRole !== 'admin') {
            $err = 'You cannot revoke your own administrator status.';
            if ($request->wantsJson()) {
                return response()->json(['error' => $err], 400);
            }
            return back()->withErrors(['role' => $err]);
        }

        $oldRole = $user->role;
        $user->role = $newRole;
        $user->save();

        AdminActivityLog::record(
            'user_role_changed',
            "Changed role for user [{$user->email}] from '{$oldRole}' to '{$newRole}'",
            $user,
            ['old_role' => $oldRole, 'new_role' => $newRole]
        );

        $msg = "Role for [{$user->email}] updated to {$newRole}.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'role' => $user->role, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Grant free trial extension or manual subscription to a user.
     */
    public function updateSubscription(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $user = User::findOrFail($id);

        $request->validate([
            'action' => ['required', Rule::in(['extend_trial', 'grant_subscription', 'expire_access'])],
            'days'   => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $action = $request->input('action');
        $days   = (int) $request->input('days', 30);

        if ($action === 'extend_trial') {
            $base = ($user->trial_ends_at && $user->trial_ends_at->isFuture()) ? $user->trial_ends_at : now();
            $user->trial_ends_at = $base->copy()->addDays($days);
            $user->subscription_status = 'trial';
            $desc = "Extended free trial by {$days} days (ends at {$user->trial_ends_at->toDateString()})";
        } elseif ($action === 'grant_subscription') {
            $base = ($user->subscription_ends_at && $user->subscription_ends_at->isFuture()) ? $user->subscription_ends_at : now();
            $user->subscription_ends_at = $base->copy()->addDays($days);
            $user->subscription_status = 'active';
            $desc = "Granted subscription of {$days} days (valid until {$user->subscription_ends_at->toDateString()})";
        } else {
            $user->trial_ends_at = now()->subMinute();
            $user->subscription_ends_at = now()->subMinute();
            $user->subscription_status = 'expired';
            $desc = "Terminated user subscription and trial access";
        }

        $user->save();

        AdminActivityLog::record(
            'subscription_adjusted',
            "{$desc} for user [{$user->email}]",
            $user,
            [
                'action' => $action,
                'days'   => $days,
                'status' => $user->subscription_status,
                'trial_ends_at' => $user->trial_ends_at?->toIso8601String(),
                'subscription_ends_at' => $user->subscription_ends_at?->toIso8601String(),
            ]
        );

        $msg = "User subscription updated: {$desc}.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'user' => $user, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }
}

