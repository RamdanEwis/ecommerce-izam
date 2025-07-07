<?php

use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
|
| Here are the admin-specific routes moved from the original api.php file.
| These routes require authentication and admin privileges.
|
*/

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // Admin Order Management - Read Operations
    Route::middleware(['throttle:admin_read'])->prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/by-date-range', [OrderController::class, 'byDateRange'])->name('by-date-range');
        Route::get('/by-amount-range', [OrderController::class, 'byAmountRange'])->name('by-amount-range');
        Route::get('/total-revenue', [OrderController::class, 'totalRevenue'])->name('total-revenue');
        Route::get('/statistics', [OrderController::class, 'allStatistics'])->name('statistics');
    });

    // Admin Product Management - Read Operations
    Route::middleware(['throttle:admin_read'])->prefix('products')->name('products.')->group(function () {
        Route::get('/statistics', [ProductController::class, 'statistics'])->name('statistics');
        Route::get('/cache-statistics', [ProductController::class, 'cacheStatistics'])->name('cache-statistics');
    });

    // Admin Product Management - Write Operations
    Route::middleware(['throttle:admin_write'])->prefix('products')->name('products.')->group(function () {
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::put('/{id}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->name('destroy');
        Route::put('/{id}/stock', [ProductController::class, 'updateStock'])->name('update-stock');
    });

    // Admin Product Management - Bulk Operations
    Route::middleware(['throttle:bulk_operations'])->prefix('products')->name('products.')->group(function () {
        Route::put('/bulk-update', [ProductController::class, 'bulkUpdate'])->name('bulk-update');
    });
});
