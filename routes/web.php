<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/**
 * Web Routes
 *
 * This file handles web interface routes. For API endpoints, see routes/api.php
 */

Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to SIM-LKD API',
        'version' => '1.0',
        'description' => 'Sistem Informasi Manajemen Koperasi',
        'documentation' => '/api/documentation',
        'api_endpoints' => [
            'Authentication' => '/api/v1/auth/login',
            'Suppliers' => '/api/v1/suppliers',
            'Stock Management' => '/api/v1/stock',
            'Purchase Orders' => '/api/v1/purchase-orders',
            'Kitchen Orders' => '/api/v1/kitchen',
            'QR Code' => '/api/v1/qrcode',
            'Finance' => '/api/v1/finance',
        ],
        'note' => 'All API endpoints use POST method. Please use an API client like Postman or Thunder Client, or access the Swagger documentation at /api/documentation'
    ]);
});
