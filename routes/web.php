<?php

use App\Http\Controllers\Admin\AdminActivityLogController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminGroupController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminSubscriptionController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\RazorpayPaymentController;
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
Route::middleware('auth:web')->group(function () {
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

    Route::post('/subscription/purchase', [\App\Http\Controllers\SubscriptionController::class, 'purchase'])->name('subscription.purchase');
    Route::post('/subscription/razorpay/order', [RazorpayPaymentController::class, 'createOrder'])->name('subscription.razorpay.order');
    Route::post('/subscription/razorpay/verify', [RazorpayPaymentController::class, 'verifyPayment'])->name('subscription.razorpay.verify');
    Route::get('/subscription/orders/{order}/status', [RazorpayPaymentController::class, 'getStatus'])->name('subscription.orders.status');

    // User Telegram Group Submissions
    Route::post('/user/group-submissions', [\App\Http\Controllers\GroupSubmissionController::class, 'store'])->name('user.group-submissions.store');
    Route::get('/user/group-submissions', [\App\Http\Controllers\GroupSubmissionController::class, 'index'])->name('user.group-submissions.index');
});

// ── Razorpay Webhook (Exempt from CSRF & Session Auth) ──────────────────────
Route::post('/webhooks/razorpay', [RazorpayPaymentController::class, 'handleWebhook'])->name('webhooks.razorpay');

// ── Dedicated Admin Authentication Routes ────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
});

// ── Admin Dashboard & Management Routes ──────────────────────────────────────
Route::prefix('admin')->middleware(['admin'])->name('admin.')->group(function () {
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

    // User Sent / Submitted Group Links
    Route::get('/sent-links', [\App\Http\Controllers\Admin\AdminGroupSubmissionController::class, 'index'])->name('group-submissions.index');
    Route::post('/sent-links/{id}/accept', [\App\Http\Controllers\Admin\AdminGroupSubmissionController::class, 'accept'])->name('group-submissions.accept');
    Route::post('/sent-links/{id}/reject', [\App\Http\Controllers\Admin\AdminGroupSubmissionController::class, 'reject'])->name('group-submissions.reject');
    Route::get('/sent-links/{id}/preview', [\App\Http\Controllers\Admin\AdminGroupSubmissionController::class, 'preview'])->name('group-submissions.preview');
    Route::delete('/sent-links/{id}', [\App\Http\Controllers\Admin\AdminGroupSubmissionController::class, 'destroy'])->name('group-submissions.destroy');

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
    Route::post('/subscriptions/orders/{id}/activate', [AdminSubscriptionController::class, 'activateOrder'])->name('subscriptions.orders.activate');

    // Settings
    Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [AdminSettingController::class, 'update'])->name('settings.update');

    // Activity Logs
    Route::get('/activity-logs', [AdminActivityLogController::class, 'index'])->name('activity-logs.index');
});

// ── Telegram Photos Route (Fallback placeholder if file not copied yet) ─────
Route::get('/storage/telegram/{file}', function ($file) {
    $path = storage_path('app/public/telegram/' . $file);
    if (file_exists($path)) {
        return response()->file($path);
    }

    if (function_exists('imagecreate')) {
        $im = imagecreate(300, 300);
        $bg = imagecolorallocate($im, 24, 24, 27); // #18181b
        $fg = imagecolorallocate($im, 161, 161, 170); // #a1a1aa
        $gray = imagecolorallocate($im, 63, 63, 70); // #3f3f46
        imagestring($im, 5, 85, 130, 'Price Analyzer', $fg);
        imagestring($im, 3, 90, 155, 'Product Photo', $gray);
        ob_start();
        imagepng($im);
        $pngData = ob_get_clean();
        imagedestroy($im);

        return response($pngData, 200, [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300" viewBox="0 0 300 300">
        <rect width="100%" height="100%" fill="#18181b"/>
        <text x="50%" y="150" font-family="sans-serif" font-size="14" fill="#a1a1aa" text-anchor="middle">Product Photo</text>
    </svg>';

    return response($svg, 200, [
        'Content-Type' => 'image/svg+xml',
        'Cache-Control' => 'no-cache, private',
    ]);
})->where('file', '.*')->name('telegram.photo.fallback');

