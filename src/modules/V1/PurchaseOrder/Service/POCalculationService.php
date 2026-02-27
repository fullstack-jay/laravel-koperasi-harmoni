<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Service;

use Modules\V1\PurchaseOrder\Models\PurchaseOrder;

final class POCalculationService
{
    public function calculateEstimatedTotal(array $items): float
    {
        return collect($items)->sum(function ($item) {
            return $item['estimated_unit_price'] * $item['estimated_qty'];
        });
    }

    public function calculateActualTotal(array $items): float
    {
        return collect($items)->sum(function ($item) {
            return ($item['actual_unit_price'] ?? 0) * ($item['actual_qty'] ?? 0);
        });
    }

    public function calculateItemSubtotal(float $unitPrice, int $qty): float
    {
        return $unitPrice * $qty;
    }

    public function updatePOTotal(PurchaseOrder $po): void
    {
        $po->estimated_total = $this->calculateEstimatedTotal($po->items->toArray());
        $po->actual_total = $this->calculateActualTotal($po->items->toArray());
        $po->save();
    }
}
