<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupSubmissionController;
use App\Http\Controllers\SseController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// ── Mobile & Web Public/Semi-Public Endpoints ────────────────────────────────
Route::middleware(['web'])->group(function () {
    // Market Intelligence & Price Board
    Route::get('/groups',                  [GroupController::class, 'index']);
    Route::get('/board',                   [BoardController::class, 'index']);
    Route::get('/board/stats',             [BoardController::class, 'stats']);
    Route::post('/board/hash-photo',       [BoardController::class, 'hashPhoto']);
    Route::get('/canonical-products/{id}', [BoardController::class, 'show']);
    Route::get('/products/{id}/history',   [BoardController::class, 'history']);

    // Authentication & Profile / Account
    Route::post('/login',                  [AuthController::class, 'login']);
    Route::post('/register',               [AuthController::class, 'register']);
    Route::post('/logout',                 [AuthController::class, 'logout']);
    Route::get('/profile',                 [AuthController::class, 'showProfile']);
    Route::put('/profile',                 [AuthController::class, 'updateProfile']);

    // User Telegram Channel Submissions
    Route::get('/user/group-submissions',  [GroupSubmissionController::class, 'index']);
    Route::post('/user/group-submissions', [GroupSubmissionController::class, 'store']);

    // ── Catalogue / Inventory ───────────────────────────────────────────────
    Route::get('/catalogue/sku-preview',   [CatalogueController::class, 'skuPreview']);
    Route::get('/catalogue',               [CatalogueController::class, 'apiIndex']);
    Route::post('/catalogue',              [CatalogueController::class, 'store']);
    Route::get('/catalogue/{id}',          [CatalogueController::class, 'show']);
    Route::post('/catalogue/{id}',         [CatalogueController::class, 'update']);
    Route::delete('/catalogue/{id}',       [CatalogueController::class, 'destroy']);

    // ── Transactions (Payment, Order, Return) ──────────────────────────────
    Route::get('/payments',                [TransactionController::class, 'getPayments']);
    Route::post('/payments',               [TransactionController::class, 'storePayment']);
    Route::post('/payments/{id}',          [TransactionController::class, 'updatePayment']);
    Route::delete('/payments/{id}',        [TransactionController::class, 'destroyPayment']);

    Route::get('/orders',                  [TransactionController::class, 'getOrders']);
    Route::post('/orders',                 [TransactionController::class, 'storeOrder']);
    Route::post('/orders/{id}',            [TransactionController::class, 'updateOrder']);
    Route::delete('/orders/{id}',          [TransactionController::class, 'destroyOrder']);

    Route::get('/return-orders',           [TransactionController::class, 'getReturnOrders']);
    Route::post('/return-orders',          [TransactionController::class, 'storeReturnOrder']);
    Route::post('/return-orders/{id}',     [TransactionController::class, 'updateReturnOrder']);
    Route::delete('/return-orders/{id}',   [TransactionController::class, 'destroyReturnOrder']);
});

// ── Administrative Group Actions (Admin Only) ───────────────────────────────
Route::middleware(['web', 'auth', 'admin'])->group(function () {
    Route::post('/groups',           [GroupController::class, 'store']);
    Route::post('/groups/sync-all',  [GroupController::class, 'syncAll']);
    Route::post('/groups/{id}/sync', [GroupController::class, 'sync']);
});
