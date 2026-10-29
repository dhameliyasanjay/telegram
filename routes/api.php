<?php

use App\Http\Controllers\BoardController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\SseController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    // ── Groups ──────────────────────────────────────────────────────────────────
    Route::get('/groups',            [GroupController::class, 'index']);
    
    // Administrative Group Actions (Admin Only)
    Route::middleware('admin')->group(function () {
        Route::post('/groups',           [GroupController::class, 'store']);
        Route::post('/groups/sync-all',   [GroupController::class, 'syncAll']);
        Route::post('/groups/{id}/sync', [GroupController::class, 'sync']);
    });

    // ── Board ───────────────────────────────────────────────────────────────────
    Route::get('/board',                  [BoardController::class, 'index']);
    Route::get('/board/stats',            [BoardController::class, 'stats']);
    Route::post('/board/hash-photo',      [BoardController::class, 'hashPhoto']);
    Route::get('/canonical-products/{id}', [BoardController::class, 'show']);
    Route::get('/products/{id}/history',  [BoardController::class, 'history']);

    // ── Catalogue ───────────────────────────────────────────────────────────────
    Route::get('/catalogue/sku-preview', [CatalogueController::class, 'skuPreview']);
    Route::get('/catalogue',             [CatalogueController::class, 'apiIndex']);
    Route::post('/catalogue',            [CatalogueController::class, 'store']);
    Route::get('/catalogue/{id}',        [CatalogueController::class, 'show']);
    Route::post('/catalogue/{id}',       [CatalogueController::class, 'update']);
    Route::delete('/catalogue/{id}',     [CatalogueController::class, 'destroy']);

    // ── Transactions (Payment, Order, Return) ──────────────────────────────────
    Route::get('/payments',              [TransactionController::class, 'getPayments']);
    Route::post('/payments',             [TransactionController::class, 'storePayment']);
    Route::post('/payments/{id}',        [TransactionController::class, 'updatePayment']);
    Route::delete('/payments/{id}',      [TransactionController::class, 'destroyPayment']);

    Route::get('/orders',                [TransactionController::class, 'getOrders']);
    Route::post('/orders',               [TransactionController::class, 'storeOrder']);
    Route::post('/orders/{id}',          [TransactionController::class, 'updateOrder']);
    Route::delete('/orders/{id}',        [TransactionController::class, 'destroyOrder']);

    Route::get('/return-orders',         [TransactionController::class, 'getReturnOrders']);
    Route::post('/return-orders',        [TransactionController::class, 'storeReturnOrder']);
    Route::post('/return-orders/{id}',   [TransactionController::class, 'updateReturnOrder']);
    Route::delete('/return-orders/{id}', [TransactionController::class, 'destroyReturnOrder']);
});
