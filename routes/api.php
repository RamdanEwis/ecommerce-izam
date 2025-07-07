<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public Product Routes
Route::prefix('products')->group(function () {
    Route::get('/', [App\Http\Controllers\API\ProductController::class, 'index']);
    Route::get('/{id}', [App\Http\Controllers\API\ProductController::class, 'show']);
    Route::get('/search', [App\Http\Controllers\API\ProductController::class, 'search']);
    Route::get('/low-stock', [App\Http\Controllers\API\ProductController::class, 'lowStock']);
    Route::get('/popular', [App\Http\Controllers\API\ProductController::class, 'popular']);
    Route::get('/featured', [App\Http\Controllers\API\ProductController::class, 'featured']);
    Route::get('/recent', [App\Http\Controllers\API\ProductController::class, 'recent']);
    Route::get('/in-stock', [App\Http\Controllers\API\ProductController::class, 'inStock']);
    Route::get('/out-of-stock', [App\Http\Controllers\API\ProductController::class, 'outOfStock']);
});

// Protected Product Routes
Route::middleware('auth:sanctum')->prefix('products')->group(function () {
    Route::post('/', [App\Http\Controllers\API\ProductController::class, 'store']);
    Route::put('/{id}', [App\Http\Controllers\API\ProductController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\API\ProductController::class, 'destroy']);
    Route::get('/statistics', [App\Http\Controllers\API\ProductController::class, 'statistics']);
    Route::get('/cache-statistics', [App\Http\Controllers\API\ProductController::class, 'cacheStatistics']);
    Route::put('/{id}/stock', [App\Http\Controllers\API\ProductController::class, 'updateStock']);
    Route::put('/bulk-update', [App\Http\Controllers\API\ProductController::class, 'bulkUpdate']);
});

// Protected Order Routes
Route::middleware('auth:sanctum')->group(function () {
    // Orders API Resource
    Route::apiResource('/orders', App\Http\Controllers\API\OrderController::class);

    // Additional Order Routes
    Route::prefix('orders')->group(function () {
        Route::get('/my-orders', [App\Http\Controllers\API\OrderController::class, 'myOrders']);
        Route::get('/by-status', [App\Http\Controllers\API\OrderController::class, 'byStatus']);
        Route::get('/by-date-range', [App\Http\Controllers\API\OrderController::class, 'byDateRange']);
        Route::get('/by-amount-range', [App\Http\Controllers\API\OrderController::class, 'byAmountRange']);
        Route::get('/recent', [App\Http\Controllers\API\OrderController::class, 'recent']);
        Route::get('/statistics', [App\Http\Controllers\API\OrderController::class, 'statistics']);
        Route::get('/monthly-statistics', [App\Http\Controllers\API\OrderController::class, 'monthlyStatistics']);
        Route::get('/total-revenue', [App\Http\Controllers\API\OrderController::class, 'totalRevenue']);
        Route::put('/{id}/cancel', [App\Http\Controllers\API\OrderController::class, 'cancel']);
        Route::put('/{id}/complete', [App\Http\Controllers\API\OrderController::class, 'complete']);
        Route::put('/{id}/status', [App\Http\Controllers\API\OrderController::class, 'updateStatus']);
    });

    // Order Products API Resource
    Route::apiResource('/order_products', App\Http\Controllers\API\OrderProductController::class);

    // Cache Management Routes
    Route::prefix('cache')->group(function () {
        Route::get('/statistics', [App\Http\Controllers\API\ProductController::class, 'cacheStatistics']);
    });
});

