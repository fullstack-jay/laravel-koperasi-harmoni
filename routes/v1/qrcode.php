<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\V1\QRCode\Controllers\QRCodeController;

/**
 * QR Code Routes
 */
Route::middleware(['auth:sanctum', 'role:gudang'])->prefix('api/v1')->group(function () {
    Route::post('/qrcode/generate', [QRCodeController::class, 'generate']);
    Route::post('/qrcode/verify', [QRCodeController::class, 'verify']);
    Route::post('/qrcode/detail/{id}', [QRCodeController::class, 'detail']);
});
