<?php

declare(strict_types=1);

namespace Modules\V1\Supplier\DTOs;

class SupplierData
{
    public function __construct(
        public readonly ?string $code,
        public readonly string $name,
        public readonly string $contactPerson,
        public readonly ?string $phone,
        public readonly string $email,
        public readonly ?string $address,
        public readonly ?string $district,
        public readonly ?string $supplierType,
        public readonly ?string $category,
        public readonly ?string $companyCode,
        public readonly ?string $location,
        public readonly ?string $year,
        public readonly ?string $sequence,
    ) {}

    /**
     * Create from request data (supports both nested and root level)
     *
     * @param array $data
     * @param string $defaultEmail
     * @param string $defaultContactPerson
     * @return self
     */
    public static function fromRequest(array $data, string $defaultEmail, string $defaultContactPerson): self
    {
        // Support nested supplier_data.* or root level fields
        $supplierData = $data['supplier_data'] ?? [];

        $category = $supplierData['supplier_category'] ?? $data['supplierKategori'] ?? null;
        $companyCode = $supplierData['supplier_company_code'] ?? $data['supplierKodePerusahaan'] ?? null;
        $location = $supplierData['supplier_location'] ?? $data['supplierLokasi'] ?? null;
        $year = $supplierData['supplier_year'] ?? $data['supplierTahun'] ?? null;
        $sequence = $supplierData['supplier_sequence'] ?? $data['supplierUrut'] ?? null;

        // Parse full supplier code if provided in format: SUP-{TIPE}-{KODE PERUSAHAAN}-{KODE DAERAH}-{TAHUN}-{NOMOR URUT}
        $fullSupplierCode = $supplierData['supplier_code'] ?? null;
        $code = null;

        if (!empty($fullSupplierCode) && strpos($fullSupplierCode, 'SUP-') === 0 && empty($category)) {
            $parts = explode('-', $fullSupplierCode);
            if (count($parts) === 6) {
                $category = $category ?: ($parts[1] ?? '');
                $companyCode = $companyCode ?: ($parts[2] ?? '');
                $location = $location ?: ($parts[3] ?? '');
                $year = $year ?: ($parts[4] ?? '');
                $sequence = $sequence ?: ($parts[5] ?? '');
            }
        }

        // Assemble supplier_code from separate components if provided, otherwise use full code
        if ($category && $companyCode && $location && $year && $sequence) {
            $code = 'SUP-' . $category . '-' . $companyCode . '-' . $location . '-' . $year . '-' . $sequence;
        } else {
            $code = $fullSupplierCode;
        }

        return new self(
            code: $code,
            name: $supplierData['name'] ?? $data['supplierNama'] ?? '',
            contactPerson: $supplierData['contact_person'] ?? $data['supplierContactPerson'] ?? $defaultContactPerson,
            phone: $supplierData['phone'] ?? $data['supplierPhone'] ?? null,
            email: $supplierData['email'] ?? $data['supplierEmail'] ?? $defaultEmail,
            address: $supplierData['address'] ?? $data['supplierAddress'] ?? null,
            district: $supplierData['district'] ?? $data['supplierDistrict'] ?? null,
            supplierType: $supplierData['supplier_type'] ?? $data['supplierKategori'] ?? null,
            category: $category,
            companyCode: $companyCode,
            location: $location,
            year: $year,
            sequence: $sequence,
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
            'contact_person' => $this->contactPerson,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'district' => $this->district,
            'supplier_type' => $this->supplierType,
            'status' => 'active',
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
