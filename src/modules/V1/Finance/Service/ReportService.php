<?php

declare(strict_types=1);

namespace Modules\V1\Finance\Service;

use Illuminate\Support\Facades\DB;
use Modules\V1\Finance\Enums\TransactionTypeEnum;

final class ReportService
{
    public function getProfitSummary(string $startDate, string $endDate): array
    {
        $transactions = DB::table('transactions')
            ->whereBetween('date', [$startDate, $endDate])
            ->where('type', TransactionTypeEnum::SALES->value)
            ->selectRaw('
                SUM(amount) as total_revenue,
                SUM(profit) as total_profit,
                AVG(margin) as average_margin,
                COUNT(*) as total_transactions
            ')
            ->first();

        return [
            'total_revenue' => (float) ($transactions->total_revenue ?? 0),
            'total_profit' => (float) ($transactions->total_profit ?? 0),
            'average_margin' => (float) ($transactions->average_margin ?? 0),
            'total_transactions' => (int) ($transactions->total_transactions ?? 0),
        ];
    }

    public function getCashflowReport(string $startDate, string $endDate): array
    {
        $sales = DB::table('transactions')
            ->whereBetween('date', [$startDate, $endDate])
            ->where('type', TransactionTypeEnum::SALES->value)
            ->where('payment_status', 'paid')
            ->sum('amount');

        $purchases = DB::table('transactions')
            ->whereBetween('date', [$startDate, $endDate])
            ->where('type', TransactionTypeEnum::PURCHASE->value)
            ->where('payment_status', 'paid')
            ->sum('amount');

        return [
            'cash_in' => (float) $sales,
            'cash_out' => (float) $purchases,
            'net_cashflow' => (float) ($sales - $purchases),
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ];
    }

    public function getOmsetByDapur(string $startDate, string $endDate): array
    {
        $results = DB::table('transactions')
            ->whereBetween('date', [$startDate, $endDate])
            ->where('type', TransactionTypeEnum::SALES->value)
            ->join('dapurs', 'transactions.dapur_id', '=', 'dapurs.id')
            ->groupBy('dapurs.id', 'dapurs.name')
            ->selectRaw('
                dapurs.id,
                dapurs.name,
                SUM(transactions.amount) as total_omset,
                SUM(transactions.profit) as total_profit,
                COUNT(transactions.id) as total_orders
            ')
            ->get();

        return $results->map(fn($item) => [
            'dapur_id' => $item->id,
            'dapur_name' => $item->name,
            'total_omset' => (float) $item->total_omset,
            'total_profit' => (float) $item->total_profit,
            'total_orders' => (int) $item->total_orders,
        ])->toArray();
    }

    public function getOmsetByItem(string $startDate, string $endDate): array
    {
        $results = DB::table('transaction_items')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->join('stock_items', 'transaction_items.item_id', '=', 'stock_items.id')
            ->groupBy('stock_items.id', 'stock_items.name')
            ->selectRaw('
                stock_items.id,
                stock_items.name,
                SUM(transaction_items.qty) as total_qty_sold,
                SUM(transaction_items.subtotal) as total_omset,
                SUM(transaction_items.profit) as total_profit,
                AVG(transaction_items.margin) as avg_margin
            ')
            ->get();

        return $results->map(fn($item) => [
            'item_id' => $item->id,
            'item_name' => $item->name,
            'total_qty_sold' => (int) $item->total_qty_sold,
            'total_omset' => (float) $item->total_omset,
            'total_profit' => (float) $item->total_profit,
            'average_margin' => (float) $item->avg_margin,
        ])->toArray();
    }
}
