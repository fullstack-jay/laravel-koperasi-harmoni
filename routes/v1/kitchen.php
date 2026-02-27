<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\V1\Kitchen\Controllers\KitchenController;

/*
|--------------------------------------------------------------------------
| Kitchen Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'role:dapur'])->group(function () {
    // List and view orders
    Route::post('/kitchen-orders/list', [KitchenController::class, 'index']);
    Route::post('/kitchen-orders/{order}', [KitchenController::class, 'show']);

    // Create order
    Route::post('/kitchen-orders/create', [KitchenController::class, 'create']);

    // Send order to dapur
    Route::post('/kitchen-orders/{order}/send', [KitchenController::class, 'send']);

    // Process order (approve items and allocate stock)
    Route::post('/kitchen-orders/{order}/process', [KitchenController::class, 'process']);

    // Deliver order
    Route::post('/kitchen-orders/{order}/deliver', [KitchenController::class, 'deliver']);
});
