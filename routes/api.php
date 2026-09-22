<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderBatchController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RegionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// region routes
// هذه المجموعة محمية بتسجيل الدخول (Sanctum)
Route::middleware('auth:sanctum')->group(function () {

    // مسار عرض المناطق متاح لكل المستخدمين المسجلين (لأن التاجر يحتاج يرى المناطق عند تقديم طلب)
    Route::get('/regions', [RegionController::class, 'index']);

    // --- مجموعة المسارات الخاصة بالأدمن فقط ---
    Route::middleware('role:admin')->group(function () {
        Route::post('/regions', [RegionController::class, 'store']);
        Route::put('/regions/{id}', [RegionController::class, 'update']);
        Route::delete('/regions/{id}', [RegionController::class, 'destroy']);
    });
});

// order routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'showOrders']);
    Route::get('/orders/{trackingCode}', [OrderController::class, 'showByTrackingCode']);
});

// order batch routes
Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:admin')->group(function () {
        Route::post('/order-batches/{batchCode}', [OrderBatchController::class, 'store']);
        Route::put('/order-batches/{batchCode}/remove', [OrderBatchController::class, 'removeOrdersFromBatch']);
        Route::delete('/order-batches/{batchCode}', [OrderBatchController::class, 'removeBatch']);
    });
    Route::middleware('role:admin|driver')->group(function () {
        Route::get('/order-batches/{batchCode}', [OrderBatchController::class, 'showBatchCode']);
    });
});
