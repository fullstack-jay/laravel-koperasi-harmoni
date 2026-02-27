<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Services;

use Modules\V1\Stock\Enums\AlertTypeEnum;
use Modules\V1\Stock\Models\StockAlert;
use Modules\V1\Stock\Models\StockBatch;
use Modules\V1\Stock\Models\StockItem;

class StockAlertService
{
    /**
     * Check and generate low stock alerts
     */
    public function checkLowStock(): void
    {
        $items = StockItem::all();

        foreach ($items as $item) {
            if ($item->isOutOfStock()) {
                $this->createAlert([
                    'item_id' => $item->id,
                    'batch_id' => null,
                    'alert_type' => AlertTypeEnum::OUT_OF_STOCK->value,
                    'severity' => 'critical',
                    'message' => "Item {$item->name} is out of stock",
                    'current_qty' => $item->current_stock,
                    'threshold' => $item->min_stock,
                ]);
            } elseif ($item->isLowStock()) {
                $this->createAlert([
                    'item_id' => $item->id,
                    'batch_id' => null,
                    'alert_type' => AlertTypeEnum::LOW_STOCK->value,
                    'severity' => 'warning',
                    'message' => "Item {$item->name} is below minimum stock level",
                    'current_qty' => $item->current_stock,
                    'threshold' => $item->min_stock,
                ]);
            }
        }
    }

    /**
     * Check and generate expiry alerts
     */
    public function checkExpiry(int $warningDays = 7): void
    {
        $expiringBatches = StockBatch::where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays($warningDays))
            ->where('status', 'available')
            ->get();

        foreach ($expiringBatches as $batch) {
            $daysToExpiry = now()->diffInDays($batch->expiry_date);

            $this->createAlert([
                'item_id' => $batch->item_id,
                'batch_id' => $batch->id,
                'alert_type' => AlertTypeEnum::EXPIRING_SOON->value,
                'severity' => $daysToExpiry <= 3 ? 'critical' : 'warning',
                'message' => "Batch {$batch->batch_number} will expire in {$daysToExpiry} days",
                'current_qty' => $batch->remaining_qty,
                'expiry_date' => $batch->expiry_date->format('Y-m-d'),
                'days_to_expiry' => $daysToExpiry,
            ]);
        }

        // Check for expired batches
        $expiredBatches = StockBatch::where('expiry_date', '<=', now())
            ->where('status', '!=', 'expired')
            ->get();

        foreach ($expiredBatches as $batch) {
            $this->createAlert([
                'item_id' => $batch->item_id,
                'batch_id' => $batch->id,
                'alert_type' => AlertTypeEnum::EXPIRED->value,
                'severity' => 'critical',
                'message' => "Batch {$batch->batch_number} has expired",
                'current_qty' => $batch->remaining_qty,
                'expiry_date' => $batch->expiry_date->format('Y-m-d'),
                'days_to_expiry' => 0,
            ]);
        }
    }

    /**
     * Create a stock alert
     */
    protected function createAlert(array $data): StockAlert
    {
        // Check if similar unresolved alert exists
        $existingAlert = StockAlert::where('item_id', $data['item_id'])
            ->where('batch_id', $data['batch_id'] ?? null)
            ->where('alert_type', $data['alert_type'])
            ->where('is_resolved', false)
            ->first();

        if ($existingAlert) {
            // Update existing alert
            $existingAlert->update($data);

            return $existingAlert->fresh();
        }

        return StockAlert::create($data);
    }

    /**
     * Get unresolved alerts
     */
    public function getUnresolvedAlerts(?string $type = null)
    {
        $query = StockAlert::unresolved();

        if ($type) {
            $query->where('alert_type', $type);
        }

        return $query->orderBy('severity', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Resolve an alert
     */
    public function resolveAlert(string $alertId): bool
    {
        $alert = StockAlert::find($alertId);

        if (! $alert) {
            return false;
        }

        return $alert->markAsResolved();
    }
}
