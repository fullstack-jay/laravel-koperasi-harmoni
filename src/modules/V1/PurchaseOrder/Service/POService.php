<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Service;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\V1\PurchaseOrder\Enums\POStatusEnum;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;
use Modules\V1\PurchaseOrder\Models\PurchaseOrderItem;

final class POService
{
    public function __construct(
        private POStatusService $statusService,
        private POCalculationService $calculationService
    ) {}

    public function createDraftPO(array $data): PurchaseOrder
    {
        Log::info("[PO Create] Starting PO creation", [
            'supplier_id' => $data['supplier_id'],
            'items_count' => count($data['items']),
            'created_by' => $data['created_by'] ?? null
        ]);

        DB::beginTransaction();

        try {
            $poNumber = $this->generatePONumber();

            Log::info("[PO Create] Generated PO number", ['po_number' => $poNumber]);

            $estimatedTotal = $this->calculationService->calculateEstimatedTotal($data['items']);

            $po = PurchaseOrder::create([
                'po_number' => $poNumber,
                'po_date' => $data['po_date'],
                'supplier_id' => $data['supplier_id'],
                'status' => POStatusEnum::DRAFT,
                'estimated_total' => $estimatedTotal,
                'estimated_delivery_date' => $data['estimated_delivery_date'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            Log::info("[PO Create] Created PO header", [
                'po_id' => $po->id,
                'po_number' => $po->po_number,
                'estimated_total' => $estimatedTotal
            ]);

            foreach ($data['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'item_id' => $item['item_id'],
                    'estimated_unit_price' => $item['estimated_unit_price'],
                    'estimated_qty' => $item['estimated_qty'],
                    'estimated_subtotal' => $this->calculationService->calculateItemSubtotal(
                        $item['estimated_unit_price'],
                        $item['estimated_qty']
                    ),
                    'notes' => $item['notes'] ?? null,
                ]);

                Log::info("[PO Create] Added PO item", [
                    'po_id' => $po->id,
                    'item_id' => $item['item_id'],
                    'estimated_qty' => $item['estimated_qty'],
                    'estimated_unit_price' => $item['estimated_unit_price']
                ]);
            }

            DB::commit();

            Log::info("[PO Create] PO created successfully", [
                'po_id' => $po->id,
                'po_number' => $po->po_number,
                'total_items' => count($data['items'])
            ]);

            return $po->load('items');
        } catch (Exception $e) {
            DB::rollBack();

            Log::error("[PO Create] Failed to create PO", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function sendToSupplier(string $poId, ?string $userId = null): PurchaseOrder
    {
        Log::info("[PO Send] Starting PO send to supplier", [
            'po_id' => $poId,
            'user_id' => $userId
        ]);

        $po = PurchaseOrder::with('items')->find($poId);

        if (!$po) {
            Log::error("[PO Send] PO not found", ['po_id' => $poId]);
            throw new Exception('Purchase Order not found');
        }

        Log::info("[PO Send] PO found", [
            'po_id' => $poId,
            'po_number' => $po->po_number,
            'current_status' => $po->status->value
        ]);

        // Validate PO can be sent (must be in DRAFT or DIBATALKAN_DRAFT status)
        $allowedStatuses = [POStatusEnum::DRAFT, POStatusEnum::DIBATALKAN_DRAFT];

        if (!in_array($po->status, $allowedStatuses)) {
            Log::error("[PO Send] Invalid PO status for sending", [
                'po_id' => $poId,
                'po_number' => $po->po_number,
                'current_status' => $po->status->value,
                'allowed_statuses' => array_map(fn($s) => $s->value, $allowedStatuses)
            ]);

            throw new Exception(
                "Purchase Order tidak dapat dikirim. Status saat ini: {$po->status->getLabel()}. " .
                "Hanya PO dengan status Draft atau Draft (Dibatalkan) yang dapat dikirim."
            );
        }

        Log::info("[PO Send] Transitioning PO status to TERKIRIM", [
            'po_id' => $poId,
            'po_number' => $po->po_number,
            'from_status' => $po->status->value
        ]);

        $this->statusService->transitionStatus(
            $po,
            POStatusEnum::TERKIRIM,
            'PO sent to supplier',
            $userId
        );

        Log::info("[PO Send] PO sent to supplier successfully", [
            'po_id' => $poId,
            'po_number' => $po->po_number,
            'new_status' => $po->fresh()->status->value,
            'items_count' => $po->items->count()
        ]);

        return $po->fresh();
    }

    public function cancelPO(string $poId, string $reason, ?string $userId = null): PurchaseOrder
    {
        Log::info("[PO Cancel] Starting PO cancellation", [
            'po_id' => $poId,
            'user_id' => $userId,
            'reason' => $reason
        ]);

        $po = PurchaseOrder::find($poId);

        if (!$po) {
            Log::error("[PO Cancel] PO not found", ['po_id' => $poId]);
            throw new Exception('Purchase Order not found');
        }

        Log::info("[PO Cancel] PO found", [
            'po_id' => $poId,
            'po_number' => $po->po_number,
            'current_status' => $po->status->value
        ]);

        // Only allow cancellation of DRAFT status
        if ($po->status !== POStatusEnum::DRAFT) {
            Log::error("[PO Cancel] Invalid PO status for cancellation", [
                'po_id' => $poId,
                'po_number' => $po->po_number,
                'current_status' => $po->status->value,
                'expected_status' => POStatusEnum::DRAFT->value
            ]);

            throw new Exception(
                "Hanya PO dengan status Draft yang dapat dibatalkan. " .
                "Status saat ini: {$po->status->getLabel()}"
            );
        }

        // Check if already cancelled
        if ($po->status === POStatusEnum::DIBATALKAN_DRAFT) {
            Log::warning("[PO Cancel] PO already cancelled", [
                'po_id' => $poId,
                'po_number' => $po->po_number
            ]);

            throw new Exception('PO sudah dibatalkan sebelumnya');
        }

        Log::info("[PO Cancel] Transitioning PO status to DIBATALKAN_DRAFT", [
            'po_id' => $poId,
            'po_number' => $po->po_number,
            'reason' => $reason
        ]);

        // Transition to DIBATALKAN_DRAFT status
        $this->statusService->transitionStatus(
            $po,
            POStatusEnum::DIBATALKAN_DRAFT,
            $reason,
            $userId
        );

        Log::info("[PO Cancel] PO cancelled successfully", [
            'po_id' => $poId,
            'po_number' => $po->po_number,
            'new_status' => $po->fresh()->status->value
        ]);

        return $po->fresh();
    }

    /**
     * Hard delete purchase order
     * Only POs with DRAFT status can be deleted
     */
    public function hardDelete(string $poId): void
    {
        Log::info("[PO Hard Delete] Starting PO hard delete", [
            'po_id' => $poId
        ]);

        $po = PurchaseOrder::find($poId);

        if (!$po) {
            Log::error("[PO Hard Delete] PO not found", ['po_id' => $poId]);
            throw new Exception('Purchase Order not found', 404);
        }

        Log::info("[PO Hard Delete] PO found", [
            'po_id' => $poId,
            'po_number' => $po->po_number,
            'current_status' => $po->status->value
        ]);

        // Validate PO status - only draft can be deleted
        if ($po->status->value !== POStatusEnum::DRAFT->value) {
            Log::error("[PO Hard Delete] Invalid PO status for deletion", [
                'po_id' => $poId,
                'po_number' => $po->po_number,
                'current_status' => $po->status->value,
                'expected_status' => POStatusEnum::DRAFT->value
            ]);

            throw new Exception(
                "Cannot delete PO in '{$po->status->value}' status. Only draft POs can be deleted.",
                422
            );
        }

        // Hard delete the PO (cascade delete will handle related records)
        $poNumber = $po->po_number;
        $po->forceDelete();

        Log::info("[PO Hard Delete] PO hard deleted successfully", [
            'po_id' => $poId,
            'po_number' => $poNumber
        ]);
    }

    private function generatePONumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = "PO-{$date}";

        $lastPO = PurchaseOrder::where('po_number', 'like', "{$prefix}%")
            ->orderBy('po_number', 'desc')
            ->first();

        if ($lastPO) {
            $lastNumber = (int) Str::after($lastPO->po_number, "{$prefix}-");
            $newNumber = str_pad((string)($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}-{$newNumber}";
    }
}
