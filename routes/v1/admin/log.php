<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\V1\Logging\Controllers\AdminActivityLogController;
use Modules\V1\Logging\Controllers\ActivityController;

// Admin Activity Logs Routes
Route::prefix('Activities')->group(function (): void {
    Route::post('LoadData', [AdminActivityLogController::class, 'activityLogs'])->name('LoadData');
    Route::post('View/{id}', [AdminActivityLogController::class, 'view'])->name('View');
    Route::post('Dashboard', [AdminActivityLogController::class, 'activityDashboard'])->name('Dashboard');
    Route::post('Storage-Stats', [ActivityController::class, 'getStorageStats'])->name('Storage.Stats');
});

