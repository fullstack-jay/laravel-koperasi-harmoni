<?php

use Illuminate\Support\Facades\Route;

use Modules\V1\Admin\Controllers\AdminController;
use Modules\V1\User\Controllers\UserController;

// Load all users (admin + regular users)
Route::post('LoadData', [AdminController::class, 'index'])->name('LoadData');

// Create new admin user
Route::post('Create', [AdminController::class, 'store'])->name('Create');

// Individual user routes
Route::post('View/{id}', [UserController::class, 'view'])->name('View');
Route::post('Update/{id}', [AdminController::class, 'update'])->name('Update');
Route::post('Delete/{id}', [UserController::class, 'destroy'])->name('Delete');
