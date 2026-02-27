<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\V1\Stock\Controllers\StockAdjustmentController;
use Modules\V1\Stock\Controllers\StockAlertController;
use Modules\V1\Stock\Controllers\StockBatchController;
use Modules\V1\Stock\Controllers\StockItemController;

Route::middleware(['auth:sanctum', 'role:gudang'])->prefix('stock')->as('stock:')->group(function (): void {
    // Stock Items
    Route::prefix('items')->as('items:')->group(function (): void {
        Route::post('list', [StockItemController::class, 'index'])->name('index');
        Route::post('create', [StockItemController::class, 'store'])->name('store');
        Route::post('detail/{id}', [StockItemController::class, 'show'])->name('show');
        Route::post('update/{id}', [StockItemController::class, 'update'])->name('update');
        Route::post('delete/{id}', [StockItemController::class, 'destroy'])->name('destroy');
    });

    // Stock Batches
    Route::prefix('batches')->as('batches:')->group(function (): void {
        Route::post('list/{itemId}', [StockBatchController::class, 'index'])->name('index');
        Route::post('detail/{id}', [StockBatchController::class, 'show'])->name('show');
    });

    // Stock Adjustments
    Route::post('adjust', [StockAdjustmentController::class, 'adjust'])->name('adjust');

    // Stock Alerts
    Route::prefix('alerts')->as('alerts:')->group(function (): void {
        Route::post('list', [StockAlertController::class, 'index'])->name('index');
        Route::post('resolve/{id}', [StockAlertController::class, 'resolve'])->name('resolve');
    });
});
