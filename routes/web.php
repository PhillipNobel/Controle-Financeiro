<?php

use App\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Health check routes
Route::get('/health', [HealthCheckController::class, 'index'])->name('health.check');
Route::get('/health/simple', [HealthCheckController::class, 'simple'])->name('health.simple');
