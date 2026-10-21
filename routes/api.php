<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| RESTful API endpoints for Authentication, Order Management,
| and Extendable Payment Processing with Strategy Pattern.
|
*/

// Public Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');
});

// Protected Routes (Require JWT Bearer Token)
Route::middleware('auth:api')->group(function () {

    // Auth actions
    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
    });

    // Orders Management
    Route::apiResource('orders', OrderController::class);

    // Payments Management
    Route::prefix('payments')->group(function () {
        Route::get('gateways', [PaymentController::class, 'gateways'])->name('payments.gateways');
        Route::post('/', [PaymentController::class, 'process'])->name('payments.process');
        Route::get('/', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('{id}', [PaymentController::class, 'show'])->name('payments.show');
    });

    // Specific Order Payments
    Route::get('orders/{orderId}/payments', [PaymentController::class, 'byOrder'])->name('orders.payments');
});
