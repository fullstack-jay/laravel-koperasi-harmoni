<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::post('/', function () {
    return response()->json(['message' => 'Version 1']);
});

/**
 * Admin Routes
 */
Route::middleware(['auth:sanctum'])->prefix('Admin')->as('admin:')->group(
    base_path('routes/v1/admin/api.php'),
);

/**
 * User Routes
 */
Route::middleware(['auth:sanctum'])->prefix('user')->as('user:')->group(
    base_path('routes/v1/users/api.php'),
);

/**
 * Authentication Routes
 */
Route::as('auth:')->group(
    base_path('routes/v1/auth.php'),
);

/**
 * Supplier Routes
 */
Route::middleware(['auth:sanctum'])->prefix('suppliers')->as('suppliers:')->group(
    base_path('routes/v1/suppliers.php'),
);

/**
 * Stock Routes
 */
Route::middleware(['auth:sanctum'])->prefix('Stock')->as('stock:')->group(
    base_path('routes/v1/stock.php'),
);

/**
 * Purchase Order Routes
 */
Route::middleware(['auth:sanctum'])->prefix('purchase-orders')->as('purchase-orders:')->group(
    base_path('routes/v1/purchase-orders.php'),
);

/**
 * QR Code Routes
 */
Route::middleware(['auth:sanctum'])->prefix('qrcode')->as('qrcode:')->group(
    base_path('routes/v1/qrcode.php'),
);

/**
 * Kitchen Routes
 */
Route::middleware(['auth:sanctum'])->prefix('kitchen')->as('kitchen:')->group(
    base_path('routes/v1/kitchen.php'),
);

/**
 * Finance Routes
 */
Route::middleware(['auth:sanctum'])->prefix('finance')->as('finance:')->group(
    base_path('routes/v1/finance.php'),
);

