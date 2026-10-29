<?php

use App\Http\Controllers\Admin\AdminActivityLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminGroupController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminSubscriptionController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\SseController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// ── Guest Authentication Routes ─────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');

    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// ── Authenticated Application Routes ────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

    Route::get('/', function () {
        return view('board');
    })->name('board');

    Route::get('/sse', [SseController::class, 'stream'])->name('sse');

    Route::get('/catalogue', [CatalogueController::class, 'index'])->name('catalogue');

    Route::get('/payments', [TransactionController::class, 'paymentsIndex'])->name('payments');
    Route::get('/orders', [TransactionController::class, 'ordersIndex'])->name('orders');
    Route::get('/returns', [TransactionController::class, 'returnsIndex'])->name('returns');
});

// ── Admin Dashboard & Management Routes ──────────────────────────────────────
Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    // Telegram Groups
    Route::get('/telegram-groups', [AdminGroupController::class, 'index'])->name('groups.index');
    Route::post('/telegram-groups', [AdminGroupController::class, 'store'])->name('groups.store');
    Route::post('/telegram-groups/sync-all', [AdminGroupController::class, 'syncAll'])->name('groups.sync-all');
    Route::post('/telegram-groups/{id}/toggle', [AdminGroupController::class, 'toggleMonitoring'])->name('groups.toggle');
    Route::post('/telegram-groups/{id}/sync', [AdminGroupController::class, 'sync'])->name('groups.sync');
    Route::put('/telegram-groups/{id}', [AdminGroupController::class, 'update'])->name('groups.update');
    Route::delete('/telegram-groups/{id}', [AdminGroupController::class, 'destroy'])->name('groups.destroy');

    // Products & Offers
    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/products/{id}', [AdminProductController::class, 'show'])->name('products.show');
    Route::put('/products/{id}', [AdminProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{canonicalId}/offers/{offerId}', [AdminProductController::class, 'destroyOffer'])->name('products.offers.destroy');

    // Users
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/users/{id}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('/users/{id}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
    Route::post('/users/{id}/subscription', [AdminUserController::class, 'updateSubscription'])->name('users.subscription');

    // Subscriptions
    Route::get('/subscriptions', [AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions/plan', [AdminSubscriptionController::class, 'updatePlan'])->name('subscriptions.plan');

    // Settings
    Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [AdminSettingController::class, 'update'])->name('settings.update');

    // Activity Logs
    Route::get('/activity-logs', [AdminActivityLogController::class, 'index'])->name('activity-logs.index');
});

