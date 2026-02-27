<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Service;

use Exception;
use Modules\V1\PurchaseOrder\Enums\POStatusEnum;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;
use Modules\V1\PurchaseOrder\Models\POStatusHistory;

final class POStatusService
{
    public function transitionStatus(PurchaseOrder $po, POStatusEnum $newStatus, ?string $notes = null, ?string $userId = null): void
    {
        if (!$po->canTransitionTo($newStatus)) {
            throw new Exception(
                "Cannot transition from {$po->status->value} to {$newStatus->value}"
            );
        }

        $oldStatus = $po->status;

        $po->status = $newStatus;
        $po->save();

        POStatusHistory::create([
            'purchase_order_id' => $po->id,
            'from_status' => $oldStatus?->value,
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
}
