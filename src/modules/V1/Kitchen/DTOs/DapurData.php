<?php

declare(strict_types=1);

namespace Modules\V1\Kitchen\DTOs;

class DapurData
{
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $location,
        public readonly string $picName,
        public readonly ?string $picPhone,
    ) {}

    /**
     * Create from request data (supports both nested and root level)
     *
     * @param array $data
     * @param string $defaultName
     * @param string $defaultPicName
     * @return self
     */
    public static function fromRequest(array $data, string $defaultName, string $defaultPicName): self
    {
        $dapurData = $data['dapur_data'] ?? [];

        $fullDapurCode = $dapurData['dapur_code'] ?? $data['dapurKode'] ?? null;
        $dapurZona = $dapurData['dapur_zona'] ?? $data['dapurZona'] ?? null;
        $dapurTahun = $dapurData['dapur_year'] ?? $data['dapurTahun'] ?? null;
        $dapurUrut = $dapurData['dapur_sequence'] ?? $data['dapurUrut'] ?? null;

        // Use full code directly if it's in correct format
        if (!empty($fullDapurCode) && strpos($fullDapurCode, 'DPR-') === 0) {
            $dapurFullCode = $fullDapurCode;
        } elseif (!empty($fullDapurCode) && !empty($dapurZona) && !empty($dapurTahun) && !empty($dapurUrut)) {
            // Assemble from components
            $dapurFullCode = 'DPR-' . $fullDapurCode . '-' . $dapurZona . '-' . $dapurTahun . '-' . $dapurUrut;
        } else {
            // Auto-generate code
            $lastDapur = \Modules\V1\Kitchen\Models\Dapur::withTrashed()->orderBy('code', 'desc')->first();
            $lastNumber = $lastDapur ? (int)str_replace('DAP-', '', $lastDapur->code) : 0;
            $dapurFullCode = 'DAP-' . str_pad((string)($lastNumber + 1), 3, '0', STR_PAD_LEFT);
        }

        return new self(
            code: $dapurFullCode,
            name: $dapurData['name'] ?? $data['dapurNama'] ?? $defaultName,
            location: $dapurData['location'] ?? $data['dapurLocation'] ?? null,
            picName: $dapurData['pic_name'] ?? $data['dapurPicName'] ?? $defaultPicName,
            picPhone: $dapurData['pic_phone'] ?? $data['dapurPicPhone'] ?? null,
        );
    }

    /**
     * Convert to array for database insertion
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'location' => $this->location,
            'pic_name' => $this->picName,
            'pic_phone' => $this->picPhone,
            'status' => 'active',
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
