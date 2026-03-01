<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\V1\Supplier\Controllers\SupplierController;
use Modules\V1\Supplier\Controllers\SupplierUpdateHargaController;
use Modules\V1\Supplier\Controllers\SupplierPengirimanController;

Route::middleware(['auth:sanctum', 'role:koperasi'])->group(function (): void {
    Route::post('list', [SupplierController::class, 'index'])->name('index');
    Route::post('create', [SupplierController::class, 'store'])->name('store');
    Route::post('detail/{id}', [SupplierController::class, 'show'])->name('show');
    Route::post('update/{id}', [SupplierController::class, 'update'])->name('update');
    Route::post('delete/{id}', [SupplierController::class, 'destroy'])->name('destroy');
});

// Supplier Update Harga Routes - for supplier and super_admin roles
Route::middleware(['auth:sanctum'])->prefix('UpdateHarga')->group(function (): void {
    Route::post('LoadData', [SupplierUpdateHargaController::class, 'loadData'])->name('update.harga.loaddata');
});

// Supplier Pengiriman Routes - for supplier and super_admin roles
Route::middleware(['auth:sanctum'])->prefix('Pengiriman')->group(function (): void {
    Route::post('LoadData', [SupplierPengirimanController::class, 'loadData'])->name('pengiriman.loaddata');
});
