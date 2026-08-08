<?php

use App\Http\Controllers\SseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('board');
});

Route::get('/sse', [SseController::class, 'stream']);
