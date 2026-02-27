<?php

declare(strict_types=1);

namespace Modules\V1\Finance\Service;

final class ProfitCalculationService
{
    public function calculateItemProfit(float $sellPrice, float $buyPrice, int $qty): array
    {
        $revenue = $sellPrice * $qty;
        $cost = $buyPrice * $qty;
        $profit = $revenue - $cost;
        $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

        return [
            'revenue' => $revenue,
            'cost' => $cost,
            'profit' => $profit,
            'margin' => $margin,
        ];
    }

    public function calculateWeightedAverageBuyPrice(array $batches): float
    {
        $totalCost = 0;
        $totalQty = 0;

        foreach ($batches as $batch) {
            $totalCost += $batch['buyPrice'] * $batch['qty'];
            $totalQty += $batch['qty'];
        }

        return $totalQty > 0 ? $totalCost / $totalQty : 0;
    }

    public function calculateTransactionMargin(float $totalRevenue, float $totalProfit): float
    {
        return $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
    }
}
