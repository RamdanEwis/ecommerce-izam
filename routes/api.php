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


Route::apiResource('/products', App\Http\Controllers\API\ProductController::class);

Route::apiResource('/orders', App\Http\Controllers\API\OrderController::class);

Route::apiResource('/order_products', App\Http\Controllers\API\OrderProductController::class);
