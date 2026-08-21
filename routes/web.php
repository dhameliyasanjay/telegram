<?php

use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\SseController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('board');
});

Route::get('/sse', [SseController::class, 'stream']);

Route::get('/catalogue', [CatalogueController::class, 'index']);

Route::get('/payments', [TransactionController::class, 'paymentsIndex']);
Route::get('/orders', [TransactionController::class, 'ordersIndex']);
Route::get('/returns', [TransactionController::class, 'returnsIndex']);
