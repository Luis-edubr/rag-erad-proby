<?php

use App\Http\Controllers\Api\RagController;
use Illuminate\Support\Facades\Route;

Route::prefix('rag')->group(function () {
    Route::post('/search', [RagController::class, 'search']);
    Route::post('/answer', [RagController::class, 'answer']);
});
