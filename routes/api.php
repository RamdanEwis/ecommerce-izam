<?php

use App\Http\Controllers\API\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authenticated User API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for authenticated users (non-admin).
| These routes require authentication but not admin privileges.
|
*/

// ==========================
// AUTHENTICATED USER ROUTES
// ==========================

Route::middleware(['auth:sanctum', 'throttle:authenticated'])->group(function () {

    // ==========================
    // USER ORDER MANAGEMENT
    // ==========================

    // User's Own Orders - Read Operations
    Route::prefix('my-orders')->name('my-orders.')->group(function () {
        Route::get('/', [OrderController::class, 'myOrders'])->name('index');
        Route::get('/by-status', [OrderController::class, 'byStatus'])->name('by-status');
        Route::get('/statistics', [OrderController::class, 'statistics'])->name('statistics');
        Route::get('/monthly-statistics', [OrderController::class, 'monthlyStatistics'])->name('monthly-statistics');
    });

    // ==========================
    // ORDER OPERATIONS
    // ==========================

    // Order Write Operations - Stricter rate limit
    Route::middleware(['throttle:write_operations'])->prefix('orders')->name('orders.')->group(function () {
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::put('/{id}', [OrderController::class, 'update'])->name('update');
        Route::delete('/{id}', [OrderController::class, 'destroy'])->name('destroy');
        Route::put('/{id}/cancel', [OrderController::class, 'cancel'])->name('cancel');
        Route::put('/{id}/complete', [OrderController::class, 'complete'])->name('complete');
        Route::put('/{id}/status', [OrderController::class, 'updateStatus'])->name('update-status');
    });

    // Order Read Operations - Normal rate limit
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/{id}', [OrderController::class, 'show'])->name('show');
    });
});



