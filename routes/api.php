<?php

use App\Http\Controllers\BoardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\SseController;
use Illuminate\Support\Facades\Route;

// ── Groups ──────────────────────────────────────────────────────────────────
Route::get('/groups',          [GroupController::class, 'index']);
Route::post('/groups',         [GroupController::class, 'store']);
Route::post('/groups/{id}/sync', [GroupController::class, 'sync']);

// ── Board ───────────────────────────────────────────────────────────────────
Route::get('/board',           [BoardController::class, 'index']);
Route::get('/board/stats',     [BoardController::class, 'stats']);
Route::get('/products/{id}/history', [BoardController::class, 'history']);
