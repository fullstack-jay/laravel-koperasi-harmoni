<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\V1\Supplier\Controllers\SupplierController;

Route::middleware(['auth:sanctum', 'role:procurement,gudang'])->prefix('suppliers')->as('suppliers:')->group(function (): void {
    Route::post('list', [SupplierController::class, 'index'])->name('index');
    Route::post('create', [SupplierController::class, 'store'])->name('store');
    Route::post('detail/{id}', [SupplierController::class, 'show'])->name('show');
    Route::post('update/{id}', [SupplierController::class, 'update'])->name('update');
    Route::post('delete/{id}', [SupplierController::class, 'destroy'])->name('destroy');
});
