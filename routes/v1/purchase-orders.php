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

Route::middleware(['auth:sanctum', 'role:koperasi'])->group(function () {
    // List and view POs
    Route::post('LoadData', [POController::class, 'index'])->name('list');

    // Create PO - must come before {po} route
    Route::post('Create', POCreateController::class)->name('create');

    // Show specific PO - must come after specific routes
    Route::post('{po}', [POController::class, 'show'])->name('show');

    // Update PO
    Route::post('{po}/update', [POUpdateController::class, 'update'])->name('update');

    // Supplier actions
    Route::post('{po}/supplier/confirm', [POSupplierController::class, 'confirm'])->name('supplier.confirm');
    Route::post('{po}/supplier/reject', [POSupplierController::class, 'reject'])->name('supplier.reject');

    // Koperasi actions
    Route::post('{po}/koperasi/confirm', [POKoperasiController::class, 'confirmSupplierResponse'])->name('koperasi.confirm');
    Route::post('{po}/koperasi/reject', [POKoperasiController::class, 'rejectSupplierResponse'])->name('koperasi.reject');
    Route::post('{po}/koperasi/receive', [POKoperasiController::class, 'receiveGoods'])->name('koperasi.receive');

    // Cancel PO
    Route::post('{po}/cancel', POCancelController::class)->name('cancel');
});
