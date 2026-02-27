<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\V1\Finance\Controllers\FinanceController;

/*
|--------------------------------------------------------------------------
| Finance Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'role:keuangan'])->group(function () {
    // List and view transactions
    Route::post('/transactions/list', [FinanceController::class, 'index']);
    Route::post('/transactions/{transaction}', [FinanceController::class, 'show']);

    // Reports
    Route::post('/reports/profit-summary', [FinanceController::class, 'profitSummary']);
    Route::post('/reports/cashflow', [FinanceController::class, 'cashflowReport']);
    Route::post('/reports/omset-by-dapur', [FinanceController::class, 'omsetByDapur']);
    Route::post('/reports/omset-by-item', [FinanceController::class, 'omsetByItem']);

    // Mark transaction as paid
    Route::post('/transactions/{transaction}/mark-paid', [FinanceController::class, 'markAsPaid']);
});
