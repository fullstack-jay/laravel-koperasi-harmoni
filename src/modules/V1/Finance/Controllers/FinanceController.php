<?php

declare(strict_types=1);

namespace Modules\V1\Finance\Controllers;

use App\Http\Controllers\V1\Controller;
use Illuminate\Http\Request;
use Modules\V1\Finance\Models\Transaction;
use Modules\V1\Finance\Resources\TransactionResource;
use Modules\V1\Finance\Services\ReportService;
use Modules\V1\Finance\Services\TransactionService;
use Shared\Helpers\ResponseHelper;

final class FinanceController extends Controller
{
    public function __construct(
        private ReportService $reportService,
        private TransactionService $transactionService
    ) {}

    /**
     * @OA\Post(
     *      path="/finance/list",
     *      summary="Get all transactions",
     *      description="Returns a paginated list of financial transactions",
     *      tags={"Finance"},
     *
     *      @OA\RequestBody(
     *
     *          @OA\MediaType(
     *              mediaType="application/json",
     *
     *              @OA\Schema(
     *
     *                  @OA\Property(property="pageNumber", type="integer", example=1, description="Page number"),
     *                  @OA\Property(property="pageSize", type="integer", example=10, description="Items per page"),
     *                  @OA\Property(property="sortDir", type="string", enum={"ASC", "DESC"}, example="ASC", description="Sort direction"),
     *                  @OA\Property(property="sortDirColumn", type="string", example="id", description="Column to sort by"),
     *                  @OA\Property(property="search", type="string", example="PURCHASE", description="Global search string")
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function index(Request $request)
    {
        $pageNumber = $request->input('pageNumber', 1);
        $pageSize = $request->input('pageSize', 15);
        $sortDirColumn = $request->input('sortDirColumn', 'date');
        $sortDir = $request->input('sortDir', 'desc');
        $search = $request->input('search', '');

        $query = Transaction::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        // Apply global search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'ILIKE', "%{$search}%")
                  ->orWhere('reference', 'ILIKE', "%{$search}%");
            });
        }

        // Apply sorting and pagination
        $offset = ($pageNumber - 1) * $pageSize;
        $transactions = $query->orderBy($sortDirColumn, $sortDir)
                              ->offset($offset)
                              ->limit($pageSize)
                              ->get();

        return ResponseHelper::success(TransactionResource::collection($transactions));
    }

    /**
     * @OA\Post(
     *     path="/finance/detail/{id}",
     *     summary="Get transaction detail",
     *     description="Get detailed information about a specific transaction",
     *     tags={"Finance"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Transaction UUID",
     *
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function show(Transaction $transaction)
    {
        return ResponseHelper::success(new TransactionResource($transaction));
    }

    /**
     * @OA\Post(
     *     path="/finance/reports/profit-summary",
     *     summary="Get profit summary report",
     *     description="Generate profit summary for a given date range",
     *     tags={"Finance Reports"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 required={"start_date", "end_date"},
     *
     *                 @OA\Property(property="start_date", type="string", format="date", example="2025-01-01"),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2025-01-31")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Report generated successfully"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function profitSummary(Request $request)
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
        ]);

        $result = $this->reportService->getProfitSummary(
            $request->start_date,
            $request->end_date
        );

        return ResponseHelper::success($result);
    }

    /**
     * @OA\Post(
     *     path="/finance/reports/cashflow",
     *     summary="Get cashflow report",
     *     description="Generate cashflow report for a given date range",
     *     tags={"Finance Reports"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 required={"start_date", "end_date"},
     *
     *                 @OA\Property(property="start_date", type="string", format="date", example="2025-01-01"),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2025-01-31")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Report generated successfully"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function cashflowReport(Request $request)
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
        ]);

        $result = $this->reportService->getCashflowReport(
            $request->start_date,
            $request->end_date
        );

        return ResponseHelper::success($result);
    }

    /**
     * @OA\Post(
     *     path="/finance/reports/omset-by-dapur",
     *     summary="Get omset (revenue) by dapur",
     *     description="Generate revenue report grouped by dapur for a given date range",
     *     tags={"Finance Reports"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 required={"start_date", "end_date"},
     *
     *                 @OA\Property(property="start_date", type="string", format="date", example="2025-01-01"),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2025-01-31")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Report generated successfully"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function omsetByDapur(Request $request)
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
        ]);

        $result = $this->reportService->getOmsetByDapur(
            $request->start_date,
            $request->end_date
        );

        return ResponseHelper::success($result);
    }

    /**
     * @OA\Post(
     *     path="/finance/reports/omset-by-item",
     *     summary="Get omset (revenue) by item",
     *     description="Generate revenue report grouped by stock item for a given date range",
     *     tags={"Finance Reports"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 required={"start_date", "end_date"},
     *
     *                 @OA\Property(property="start_date", type="string", format="date", example="2025-01-01"),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2025-01-31")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Report generated successfully"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function omsetByItem(Request $request)
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
        ]);

        $result = $this->reportService->getOmsetByItem(
            $request->start_date,
            $request->end_date
        );

        return ResponseHelper::success($result);
    }

    /**
     * @OA\Post(
     *     path="/finance/{id}/mark-paid",
     *     summary="Mark transaction as paid",
     *     description="Mark a pending transaction as paid",
     *     tags={"Finance"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Transaction UUID",
     *
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 required={"payment_date"},
     *
     *                 @OA\Property(property="payment_date", type="string", format="date", example="2025-01-15")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Transaction marked as paid"
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Transaction not found"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function markAsPaid(Request $request, Transaction $transaction)
    {
        try {
            $result = $this->transactionService->markAsPaid(
                $transaction->id,
                $request->payment_date
            );

            return ResponseHelper::success(
                data: new TransactionResource($result),
                message: 'Transaction marked as paid'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
