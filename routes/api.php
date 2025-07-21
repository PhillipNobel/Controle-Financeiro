<?php

use App\Http\Controllers\Api\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Transaction API routes with Sanctum authentication and rate limiting
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::apiResource('transactions', TransactionController::class);
});