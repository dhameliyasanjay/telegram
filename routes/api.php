<?php

use App\Http\Controllers\BoardController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\SseController;
use Illuminate\Support\Facades\Route;

// ── Groups ──────────────────────────────────────────────────────────────────
Route::get('/groups',          [GroupController::class, 'index']);
Route::post('/groups',         [GroupController::class, 'store']);
Route::post('/groups/sync-all', [GroupController::class, 'syncAll']);
Route::post('/groups/{id}/sync', [GroupController::class, 'sync']);

// ── Board ───────────────────────────────────────────────────────────────────
Route::get('/board',           [BoardController::class, 'index']);
Route::get('/board/stats',     [BoardController::class, 'stats']);
Route::post('/board/hash-photo', [BoardController::class, 'hashPhoto']);
Route::get('/products/{id}/history', [BoardController::class, 'history']);

// ── Catalogue ───────────────────────────────────────────────────────────────
Route::get('/catalogue/sku-preview', [CatalogueController::class, 'skuPreview']);
Route::get('/catalogue',             [CatalogueController::class, 'apiIndex']);
Route::post('/catalogue',            [CatalogueController::class, 'store']);
Route::get('/catalogue/{id}',        [CatalogueController::class, 'show']);
Route::post('/catalogue/{id}',       [CatalogueController::class, 'update']);
Route::delete('/catalogue/{id}',     [CatalogueController::class, 'destroy']);
