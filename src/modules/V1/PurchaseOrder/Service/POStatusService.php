<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Service;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\V1\PurchaseOrder\Enums\POStatusEnum;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;
use Modules\V1\PurchaseOrder\Models\POStatusHistory;

final class POStatusService
{
    public function transitionStatus(PurchaseOrder $po, POStatusEnum $newStatus, ?string $notes = null, ?string $userId = null): void
    {
        $currentStatus = $po->getAttributes()['status'] ?? null;

        if (!$po->canTransitionTo($newStatus)) {
            throw new Exception(
                "Cannot transition from {$currentStatus} to {$newStatus->value}"
            );
        }

        $oldStatusValue = $currentStatus;

        // Update status using query builder to avoid enum casting issues
        DB::table('purchase_orders')
            ->where('id', $po->id)
            ->update(['status' => $newStatus->value]);

        // Refresh the model to get the updated status
        $po->refresh();

        POStatusHistory::create([
            'purchase_order_id' => $po->id,
            'from_status' => $oldStatusValue,
            'to_status' => $newStatus->value,
            'notes' => $notes,
            'changed_by' => $userId,
        ]);

        $this->updateTimestamps($po, $newStatus);
    }

    private function updateTimestamps(PurchaseOrder $po, POStatusEnum $status): void
    {
        $timestamp = now();

        match ($status) {
            POStatusEnum::TERKIRIM => $po->update(['sent_to_supplier_at' => $timestamp]),
            POStatusEnum::DIKONFIRMASI_SUPPLIER => $po->update(['confirmed_by_supplier_at' => $timestamp]),
            POStatusEnum::DIKONFIRMASI_KOPERASI => $po->update(['confirmed_by_koperasi_at' => $timestamp]),
            POStatusEnum::SELESAI => $po->update(['received_at' => $timestamp]),
            default => null,
        };
    }

    public function canCancel(PurchaseOrder $po): bool
    {
        return in_array($po->status, [
            POStatusEnum::DRAFT,
            POStatusEnum::TERKIRIM,
            POStatusEnum::DIKONFIRMASI_SUPPLIER,
            POStatusEnum::DIKONFIRMASI_KOPERASI,
        ]);
    }

    /**
     * Create a history entry without changing status
     */
    public function createHistory(PurchaseOrder $po, POStatusEnum $currentStatus, ?string $notes = null, ?string $userId = null): void
    {
        POStatusHistory::create([
            'purchase_order_id' => $po->id,
            'from_status' => $currentStatus->value,
            'to_status' => $currentStatus->value,
            'notes' => $notes,
            'changed_by' => $userId,
        ]);
    }
}
