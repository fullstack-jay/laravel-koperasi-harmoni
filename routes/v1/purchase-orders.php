<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\V1\PurchaseOrder\Controllers\POController;
use Modules\V1\PurchaseOrder\Controllers\POCreateController;
use Modules\V1\PurchaseOrder\Controllers\POUpdateController;
use Modules\V1\PurchaseOrder\Controllers\POSupplierController;
use Modules\V1\PurchaseOrder\Controllers\POKoperasiController;
use Modules\V1\PurchaseOrder\Controllers\POCancelController;
use Modules\V1\PurchaseOrder\Controllers\POSendController;

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

    // Send PO to supplier - must come before Update route
    Route::post('{po}/Send', [POSendController::class, 'send'])->name('send');

    // Update PO
    Route::post('Update/{po}', [POUpdateController::class, 'update'])->name('update');

    // Koperasi actions
    Route::post('{po}/Koperasi/Confirm', [POKoperasiController::class, 'confirmSupplierResponse'])->name('koperasi.confirm');
    Route::post('{po}/Koperasi/Reject', [POKoperasiController::class, 'rejectSupplierResponse'])->name('koperasi.reject');
    Route::post('{po}/Koperasi/Receive', [POKoperasiController::class, 'receiveGoods'])->name('koperasi.receive');

    // Koperasi review price change actions
    Route::post('{po}/Koperasi/Review/Approve', [POKoperasiController::class, 'approvePriceChange'])->name('koperasi.review.approve');
    Route::post('{po}/Koperasi/Review/EditAndResend', [POKoperasiController::class, 'editAndResend'])->name('koperasi.review.edit-and-resend');

    // Cancel PO
    Route::post('{po}/cancel', POCancelController::class)->name('cancel');
});

Route::middleware(['auth:sanctum', 'role:pemasok'])->group(function () {
    // Supplier actions
    Route::post('{po}/Supplier/Confirm', [POSupplierController::class, 'confirm'])->name('supplier.confirm');
    Route::post('{po}/Supplier/Reject', [POSupplierController::class, 'reject'])->name('supplier.reject');
});
