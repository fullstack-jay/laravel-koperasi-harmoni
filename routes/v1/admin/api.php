<?php

use Illuminate\Support\Facades\Route;
use Modules\V1\Admin\Controllers\AdminController;

// Admin profile routes
Route::post('/me', [AdminController::class, 'show']);
Route::post('/update', [AdminController::class, 'update']);
Route::post('/change-password', [AdminController::class, 'changePassword']);

// Admin logout removed - using unified /auth/logout instead
// Route::post('/auth/logout', LogoutController::class)->name('logout');

/**
 * Users Routes
 */
Route::prefix('Users')->as('users:')->group(
    base_path('routes/v1/admin/users.php'),
);

Route::prefix('Logs')->as('logs:')->group(
    base_path('routes/v1/admin/log.php'),
);

/**
 * Admin Routes
 */
Route::as('')->group(
    base_path('routes/v1/admin/admin.php'),
);



