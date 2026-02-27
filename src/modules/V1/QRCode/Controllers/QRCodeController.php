<?php

declare(strict_types=1);

namespace Modules\V1\QRCode\Controllers;

use Exception;
use Modules\V1\QRCode\Models\QRCode;
use Modules\V1\QRCode\Service\QRCodeService;
use Modules\V1\QRCode\Resources\QRCodeResource;
use Shared\Helpers\ResponseHelper;

final class QRCodeController
{
    public function __construct(
        private QRCodeService $qrCodeService
    ) {
    }

    /**
     * @OA\Post(
     *     path="/qrcode/generate",
     *     summary="Generate QR code",
     *     description="Generate a QR code for order delivery or purchase receipt",
     *     tags={"QR Code"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 required={"type", "reference_id"},
     *
     *                 @OA\Property(property="type", type="string", enum={"KITCHEN_DELIVERY", "PURCHASE_RECEIPT"}, example="KITCHEN_DELIVERY"),
     *                 @OA\Property(property="reference_id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="data", type="object")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="QR code generated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *
     *                 @OA\Property(property="qr_string", type="string", example="ORD-1234567890-QR-abc123"),
     *                 @OA\Property(property="image_url", type="string", example="https://storage.example.com/qr-codes/ORD-1234567890-QR-abc123.png"),
     *                 @OA\Property(property="qr_code", type="object")
     *             ),
     *             @OA\Property(property="message", type="string", example="QR code generated successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function generate()
    {
        try {
            $type = request()->input('type');
            $referenceId = request()->input('reference_id');
            $data = request()->input('data', []);

            if ($type === 'KITCHEN_DELIVERY') {
                $result = $this->qrCodeService->generateDeliveryQR($referenceId, $data);
            } elseif ($type === 'PURCHASE_RECEIPT') {
                $result = $this->qrCodeService->generatePurchaseReceiptQR($referenceId, $data);
            } else {
                return ResponseHelper::error('Invalid QR code type', status: 400);
            }

            return ResponseHelper::success(
                data: [
                    'qr_string' => $result['qrString'],
                    'image_url' => $result['imageUrl'],
                    'qr_code' => new QRCodeResource($result['qrCode']),
                ],
                message: 'QR code generated successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                message: 'Failed to generate QR code',
                exception: $e
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/qrcode/verify",
     *     summary="Verify QR code",
     *     description="Verify a QR code by its string and mark it as scanned",
     *     tags={"QR Code"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 required={"qr_string"},
     *
     *                 @OA\Property(property="qr_string", type="string", example="ORD-1234567890-QR-abc123", description="QR code string to verify")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="QR code verification result",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *
     *                 @OA\Property(property="valid", type="boolean", example=true),
     *                 @OA\Property(property="type", type="string", example="KITCHEN_DELIVERY"),
     *                 @OA\Property(property="reference_id", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="data", type="object")
     *             ),
     *             @OA\Property(property="message", type="string", example="Success")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="QR code not found"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function verify()
    {
        try {
            $qrString = request()->input('qr_string');

            if (!$qrString) {
                return ResponseHelper::error('QR string is required', status: 400);
            }

            $result = $this->qrCodeService->verifyQR($qrString);

            return ResponseHelper::success(data: $result);
        } catch (Exception $e) {
            return ResponseHelper::error(
                message: 'Failed to verify QR code',
                exception: $e
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/qrcode/detail/{id}",
     *     summary="Get QR code detail",
     *     description="Get detailed information about a specific QR code",
     *     tags={"QR Code"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="QR Code UUID",
     *
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Success")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="QR code not found"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function detail(string $id)
    {
        try {
            $qrCode = QRCode::findOrFail($id);

            return ResponseHelper::success(
                data: new QRCodeResource($qrCode)
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                message: $e->getMessage(),
                status: $e->getCode() === 404 ? 404 : 500
            );
        }
    }
}
