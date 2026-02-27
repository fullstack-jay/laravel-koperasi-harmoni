<?php

declare(strict_types=1);

namespace Modules\V1\QRCode\Service;

use Illuminate\Support\Str;
use Modules\V1\QRCode\Models\QRCode;

final class QRCodeService
{
    public function __construct(
        private QRStorageService $storageService
    ) {}

    public function generateDeliveryQR(string $orderId, array $orderData): array
    {
        $qrData = json_encode([
            'type' => 'KITCHEN_DELIVERY',
            'orderId' => $orderId,
            'generatedAt' => now()->toIso8601String(),
            'items' => $orderData,
        ]);

        $qrString = 'ORD-' . time() . '-QR-' . Str::random(8);

        // For now, generate a simple QR code URL
        // In production, integrate with a QR code library like simple-qrcode
        $imagePath = "qr-codes/{$qrString}.png";
        $qrContent = $this->generateQRImage($qrData);

        $imageUrl = $this->storageService->store($imagePath, $qrContent);

        $qrCode = QRCode::create([
            'qr_string' => $qrString,
            'type' => 'KITCHEN_DELIVERY',
            'reference_id' => $orderId,
            'reference_type' => 'kitchen_order',
            'data' => [
                'order_id' => $orderId,
                'items' => $orderData,
            ],
            'image_path' => $imageUrl,
            'expires_at' => now()->addDays(7),
            'created_by' => auth()->id(),
        ]);

        return [
            'qrString' => $qrString,
            'imageUrl' => $imageUrl,
            'qrCode' => $qrCode,
        ];
    }

    public function verifyQR(string $qrString): array
    {
        $qrCode = QRCode::where('qr_string', $qrString)->first();

        if (!$qrCode) {
            return [
                'valid' => false,
                'message' => 'QR Code not found',
            ];
        }

        if (!$qrCode->isValid()) {
            return [
                'valid' => false,
                'message' => 'QR Code is expired or inactive',
            ];
        }

        // Mark as scanned
        $qrCode->update(['scanned_at' => now()]);

        return [
            'valid' => true,
            'type' => $qrCode->type,
            'reference_id' => $qrCode->reference_id,
            'data' => $qrCode->data,
        ];
    }

    public function generatePurchaseReceiptQR(string $poId, array $poData): array
    {
        $qrData = json_encode([
            'type' => 'PURCHASE_RECEIPT',
            'poId' => $poId,
            'generatedAt' => now()->toIso8601String(),
            'items' => $poData,
        ]);

        $qrString = 'PO-' . time() . '-QR-' . Str::random(8);

        $imagePath = "qr-codes/{$qrString}.png";
        $qrContent = $this->generateQRImage($qrData);

        $imageUrl = $this->storageService->store($imagePath, $qrContent);

        $qrCode = QRCode::create([
            'qr_string' => $qrString,
            'type' => 'PURCHASE_RECEIPT',
            'reference_id' => $poId,
            'reference_type' => 'purchase_order',
            'data' => [
                'po_id' => $poId,
                'items' => $poData,
            ],
            'image_path' => $imageUrl,
            'expires_at' => now()->addDays(30),
            'created_by' => auth()->id(),
        ]);

        return [
            'qrString' => $qrString,
            'imageUrl' => $imageUrl,
            'qrCode' => $qrCode,
        ];
    }

    public function deactivateQR(string $qrString): bool
    {
        $qrCode = QRCode::where('qr_string', $qrString)->first();

        if ($qrCode) {
            $qrCode->update(['is_active' => false]);

            return true;
        }

        return false;
    }

    private function generateQRImage(string $data): string
    {
        // Simple placeholder - in production, integrate with simple-qrcode or similar
        // For now, return a placeholder image data
        // TODO: Integrate with QR code generation library

        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    }
}
