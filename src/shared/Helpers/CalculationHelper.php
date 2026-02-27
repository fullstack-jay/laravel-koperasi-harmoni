<?php

declare(strict_types=1);

namespace Shared\Helpers;

final class CalculationHelper
{
    /**
     * Calculate weighted average price from batches
     *
     * @param  array  $batches  Array of batches with 'buyPrice' and 'qty' keys
     * @return float Weighted average price
     */
    public static function calculateWeightedAveragePrice(array $batches): float
    {
        $totalCost = 0;
        $totalQty = 0;

        foreach ($batches as $batch) {
            $totalCost += $batch['buyPrice'] * $batch['qty'];
            $totalQty += $batch['qty'];
        }

        return $totalQty > 0 ? $totalCost / $totalQty : 0;
    }

    /**
     * Calculate profit margin percentage
     *
     * @param  float  $profit  Profit amount
     * @param  float  $revenue  Total revenue
     * @return float Margin percentage
     */
    public static function calculateMargin(float $profit, float $revenue): float
    {
        return $revenue > 0 ? ($profit / $revenue) * 100 : 0;
    }

    /**
     * Calculate subtotal
     *
     * @param  float  $price  Unit price
     * @param  int  $qty  Quantity
     * @return float Subtotal
     */
    public static function calculateSubtotal(float $price, int $qty): float
    {
        return $price * $qty;
    }
}
