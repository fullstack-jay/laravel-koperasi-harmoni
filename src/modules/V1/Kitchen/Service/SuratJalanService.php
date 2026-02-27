<?php

declare(strict_types=1);

namespace Modules\V1\Kitchen\Service;

use Exception;
use Illuminate\Support\Str;
use Modules\V1\Kitchen\Models\KitchenOrder;
use Modules\V1\Kitchen\Models\SuratJalan;

final class SuratJalanService
{
    public function create(string $orderId, array $data): SuratJalan
    {
        $order = KitchenOrder::find($orderId);

        if (!$order) {
            throw new Exception('Order not found');
        }

        $sjNumber = $this->generateSJNumber();

        return SuratJalan::create([
            'sj_number' => $sjNumber,
            'kitchen_order_id' => $orderId,
            'dapur_id' => $order->dapur_id,
            'sj_date' => now()->toDateString(),
            'driver_name' => $data['driver_name'] ?? null,
            'vehicle_plate' => $data['vehicle_plate'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);
    }

    private function generateSJNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = "SJ-{$date}";

        $lastSJ = SuratJalan::where('sj_number', 'like', "{$prefix}%")
            ->orderBy('sj_number', 'desc')
            ->first();

        if ($lastSJ) {
            $lastNumber = (int) Str::after($lastSJ->sj_number, "{$prefix}-");
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}-{$newNumber}";
    }
}
