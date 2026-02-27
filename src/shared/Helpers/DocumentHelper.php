<?php

declare(strict_types=1);

namespace Shared\Helpers;

final class DocumentHelper
{
    /**
     * Generate Purchase Order number
     * Format: PO-YYYYMMDD-SEQ (e.g., PO-20250226-001)
     */
    public static function generatePONumber(string $date, int $sequence): string
    {
        $dateStr = str_replace('-', '', $date);

        return "PO-{$dateStr}-" . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Kitchen Order number
     * Format: ORD-YYYYMMDD-SEQ (e.g., ORD-20250226-001)
     */
    public static function generateKitchenOrderNumber(string $date, int $sequence): string
    {
        $dateStr = str_replace('-', '', $date);

        return "ORD-{$dateStr}-" . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Surat Jalan number
     * Format: SJ-YYYYMMDD-SEQ (e.g., SJ-20250226-001)
     */
    public static function generateSuratJalanNumber(string $date, int $sequence): string
    {
        $dateStr = str_replace('-', '', $date);

        return "SJ-{$dateStr}-" . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Batch number
     * Format: BATCH-YYYYMMDD-ITEMID-SEQ (e.g., BATCH-20250226-ITEM001-001)
     */
    public static function generateBatchNumber(string $itemId, string $date, int $sequence): string
    {
        $dateStr = str_replace('-', '', $date);

        return "BATCH-{$dateStr}-{$itemId}-" . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Supplier code
     * Format: SUP-XXX (e.g., SUP-001)
     */
    public static function generateSupplierCode(int $sequence): string
    {
        return 'SUP-' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Stock Item code
     * Format: STK-XXX (e.g., STK-001)
     */
    public static function generateStockItemCode(int $sequence): string
    {
        return 'STK-' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Dapur (Kitchen) code
     * Format: DAP-XXX (e.g., DAP-001)
     */
    public static function generateDapurCode(int $sequence): string
    {
        return 'DAP-' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
