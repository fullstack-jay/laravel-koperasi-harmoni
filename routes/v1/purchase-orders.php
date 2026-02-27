<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\V1\PurchaseOrder\Controllers\POController;
use Modules\V1\PurchaseOrder\Controllers\POCreateController;
use Modules\V1\PurchaseOrder\Controllers\POUpdateController;
use Modules\V1\PurchaseOrder\Controllers\POSupplierController;
use Modules\V1\PurchaseOrder\Controllers\POKoperasiController;
use Modules\V1\PurchaseOrder\Controllers\POCancelController;

/*
|--------------------------------------------------------------------------
| Purchase Order Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'role:procurement,gudang,keuangan'])->group(function () {
    // List and view POs
    Route::post('/purchase-orders/list', [POController::class, 'index']);
    Route::post('/purchase-orders/{po}', [POController::class, 'show']);

    // Create PO
    Route::post('/purchase-orders/create', POCreateController::class);

    // Update PO
    Route::post('/purchase-orders/{po}/update', [POUpdateController::class, 'update']);

    // Supplier actions
    Route::post('/purchase-orders/{po}/supplier/confirm', [POSupplierController::class, 'confirm']);
    Route::post('/purchase-orders/{po}/supplier/reject', [POSupplierController::class, 'reject']);

    // Koperasi actions
    Route::post('/purchase-orders/{po}/koperasi/confirm', [POKoperasiController::class, 'confirmSupplierResponse']);
    Route::post('/purchase-orders/{po}/koperasi/reject', [POKoperasiController::class, 'rejectSupplierResponse']);
    Route::post('/purchase-orders/{po}/koperasi/receive', [POKoperasiController::class, 'receiveGoods']);

    // Cancel PO
    Route::post('/purchase-orders/{po}/cancel', POCancelController::class);
});
