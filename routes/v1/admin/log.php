<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\V1\Logging\Resources\ActivityController;

Route::prefix('activities')->group(function (): void {
    Route::post('', [ActivityController::class, 'index']);
    Route::post('/user/{user}', [ActivityController::class, 'getUserActivities']);
    Route::post('/model/{type}/{id}', [ActivityController::class, 'getModelActivities']);
    Route::post('/dashboard/activities', [ActivityController::class, 'getActivityDashboard']);
});
