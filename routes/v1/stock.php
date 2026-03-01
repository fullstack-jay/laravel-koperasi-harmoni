<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\V1\Stock\Controllers\StockAdjustmentController;
use Modules\V1\Stock\Controllers\StockAlertController;
use Modules\V1\Stock\Controllers\StockBatchController;
use Modules\V1\Stock\Controllers\StockItemController;

Route::middleware(['auth:sanctum', 'role:koperasi'])->group(function (): void {
    // Categories
    Route::post('Categories/List', function () {
        return response()->json([
            'data' => \Modules\V1\Stock\Enums\CategoryEnum::getAll(),
            'message' => 'Categories retrieved successfully'
        ]);
    })->name('categories.list');

    // Stock Items
    Route::prefix('Items')->as('items:')->group(function (): void {
        Route::post('LoadData', [StockItemController::class, 'index'])->name('index');
        Route::post('Create', [StockItemController::class, 'store'])->name('store');
        Route::post('View/{id}', [StockItemController::class, 'show'])->name('show');
        Route::post('Update/{id}', [StockItemController::class, 'update'])->name('update');
        Route::post('Delete/{id}', [StockItemController::class, 'destroy'])->name('destroy');
    });

    // Stock Batches
    Route::prefix('Batches')->as('batches:')->group(function (): void {
        Route::post('list/{itemId}', [StockBatchController::class, 'index'])->name('index');
        Route::post('detail/{id}', [StockBatchController::class, 'show'])->name('show');
    });

    // Stock Adjustments
    Route::post('adjust', [StockAdjustmentController::class, 'adjust'])->name('adjust');

    // Stock Alerts
    Route::prefix('Alerts')->as('alerts:')->group(function (): void {
        Route::post('list', [StockAlertController::class, 'index'])->name('index');
        Route::post('resolve/{id}', [StockAlertController::class, 'resolve'])->name('resolve');
    });
});
