<?php

use App\Http\Controllers\API\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
|
| Here are the public API routes that don't require authentication.
| These routes were moved from the original api.php file.
|
*/

// Public Product Routes - Higher rate limit for browsing
Route::middleware(['throttle:public_browsing'])->prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/{id}', [ProductController::class, 'show'])->name('show');
    Route::get('/in-stock', [ProductController::class, 'inStock'])->name('in-stock');
    Route::get('/out-of-stock', [ProductController::class, 'outOfStock'])->name('out-of-stock');
});

// Search Routes - Moderate rate limit
Route::middleware(['throttle:search'])->prefix('products')->name('products.')->group(function () {
    Route::get('/search', [ProductController::class, 'search'])->name('search');
});
