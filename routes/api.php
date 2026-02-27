<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to SIM-LKD API',
        'version' => '1.0',
        'description' => 'Sistem Informasi Manajemen Koperasi',
        'documentation' => '/api/documentation',
        'endpoints' => [
            'Authentication' => '/api/v1/auth/login',
            'Suppliers' => '/api/v1/suppliers/*',
            'Stock Management' => '/api/v1/stock/*',
            'Purchase Orders' => '/api/v1/purchase-orders/*',
            'Kitchen Orders' => '/api/v1/kitchen/*',
            'QR Code' => '/api/v1/qrcode/*',
            'Finance' => '/api/v1/finance/*',
        ],
        'note' => 'All API endpoints use POST method. Please use API client (Postman, Thunder Client, etc.) or refer to Swagger documentation.'
    ]);
});

Route::post('/', function () {
    return response()->json(['message' => 'Welcome to SIM-LKD API']);
});

/**
 * Admin Routes (without v1 prefix for backward compatibility)
 */
Route::middleware(['auth:sanctum'])->prefix('Admin')->as('admin:')->group(
    base_path('routes/v1/admin/users.php'),
);

/**
 * Version 1
 */
Route::prefix('v1')->as('v1:')->group(
    base_path('routes/v1/api.php'),
);
