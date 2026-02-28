<?php

declare(strict_types=1);

namespace Modules\V1\Supplier\Services;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\V1\Supplier\Enums\SupplierStatusEnum;
use Modules\V1\Supplier\Models\Supplier;
use Shared\Helpers\DocumentHelper;

class SupplierService
{
    /**
     * Create a new supplier with auto-generated code
     */
    public function create(array $data): Supplier
    {
        return DB::transaction(function () use ($data) {
            // Generate supplier code
            $lastSupplier = Supplier::withTrashed()->orderBy('code', 'desc')->first();
            $sequence = $lastSupplier ? (int) str_replace('SUP-', '', $lastSupplier->code) + 1 : 1;
            $code = DocumentHelper::generateSupplierCode($sequence);

            $supplier = Supplier::create([
                'code' => $code,
                'name' => $data['name'],
                'contact_person' => $data['contact_person'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => $data['status'] ?? SupplierStatusEnum::ACTIVE->value,
            ]);

            return $supplier;
        });
    }

    /**
     * Update an existing supplier
     */
    public function update(string $id, array $data): Supplier
    {
        $supplier = $this->findById($id);

        $supplier->update([
            'name' => $data['name'] ?? $supplier->name,
            'contact_person' => $data['contact_person'] ?? $supplier->contact_person,
            'phone' => $data['phone'] ?? $supplier->phone,
            'email' => $data['email'] ?? $supplier->email,
            'address' => $data['address'] ?? $supplier->address,
            'status' => $data['status'] ?? $supplier->status,
        ]);

        return $supplier->fresh();
    }

    /**
     * Soft delete a supplier
     */
    public function delete(string $id): bool
    {
        $supplier = $this->findById($id);

        return $supplier->delete();
    }

    /**
     * Get all suppliers with pagination
     */
    public function getAll(
        int $pageNumber = 1,
        int $pageSize = 15,
        string $sortColumn = 'created_at',
        string $sortDir = 'desc',
        string $search = ''
    ): Collection {
        $query = Supplier::query();

        // Apply global search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('code', 'ILIKE', "%{$search}%")
                  ->orWhere('contact_person', 'ILIKE', "%{$search}%");
            });
        }

        // Apply sorting and pagination
        $offset = ($pageNumber - 1) * $pageSize;

        return $query->orderBy($sortColumn, $sortDir)
                     ->offset($offset)
                     ->limit($pageSize)
                     ->get();
    }

    /**
     * Find supplier by ID
     */
    public function findById(string $id): Supplier
    {
        $supplier = Supplier::find($id);

        if (! $supplier) {
            throw new Exception('Supplier not found', 404);
        }

        return $supplier;
    }

    /**
     * Find supplier by code
     */
    public function findByCode(string $code): Supplier
    {
        $supplier = Supplier::where('code', $code)->first();

        if (! $supplier) {
            throw new Exception('Supplier not found', 404);
        }

        return $supplier;
    }

    /**
     * Get or create supplier from user request data
     * Used specifically in user creation/update flows
     *
     * @param array $requestData
     * @param string $defaultEmail
     * @param string $defaultContactPerson
     * @param string|null $existingSupplierId
     * @return string|null Supplier ID or null if no supplier data
     * @throws Exception
     */
    public function getOrCreateForUser(
        array $requestData,
        string $defaultEmail,
        string $defaultContactPerson,
        ?string $existingSupplierId = null
    ): ?string {
        // If existing supplier ID is provided and no new data, return existing
        if ($existingSupplierId && !$this->hasSupplierData($requestData)) {
            return $existingSupplierId;
        }

        // If existing supplier and has new data, update it
        if ($existingSupplierId && $this->hasSupplierData($requestData)) {
            return $this->updateForUser($existingSupplierId, $requestData, $defaultContactPerson);
        }

        // Create new supplier if has data
        if ($this->hasSupplierData($requestData)) {
            return $this->createForUser($requestData, $defaultEmail, $defaultContactPerson);
        }

        return null;
    }

    /**
     * Check if request has supplier data
     */
    private function hasSupplierData(array $data): bool
    {
        $supplierData = $data['supplier_data'] ?? [];

        return !empty($supplierData['name'])
            || !empty($supplierData['supplier_code'])
            || !empty($data['supplierKategori'])
            || !empty($data['supplierNama']);
    }

    /**
     * Create new supplier from user request
     */
    private function createForUser(array $requestData, string $defaultEmail, string $defaultContactPerson): string
    {
        $supplierData = \Modules\V1\Supplier\DTOs\SupplierData::fromRequest(
            $requestData,
            $defaultEmail,
            $defaultContactPerson
        );

        if (empty($supplierData->code)) {
            throw new Exception('Supplier code is required');
        }

        $supplier = Supplier::create($supplierData->toArray());

        return $supplier->id;
    }

    /**
     * Update existing supplier from user request
     */
    private function updateForUser(string $supplierId, array $requestData, string $defaultContactPerson): string
    {
        $supplier = Supplier::find($supplierId);

        if (!$supplier) {
            throw new Exception('Supplier not found');
        }

        $supplierData = \Modules\V1\Supplier\DTOs\SupplierData::fromRequest(
            $requestData,
            $supplier->email,
            $defaultContactPerson
        );

        // Update fields if provided
        if ($supplierData->code) {
            $supplier->code = $supplierData->code;
        }
        if ($supplierData->name) {
            $supplier->name = $supplierData->name;
        }
        if ($supplierData->contactPerson) {
            $supplier->contact_person = $supplierData->contactPerson;
        }
        if ($supplierData->phone) {
            $supplier->phone = $supplierData->phone;
        }
        if ($supplierData->email) {
            $supplier->email = $supplierData->email;
        }
        if ($supplierData->address) {
            $supplier->address = $supplierData->address;
        }
        if ($supplierData->district) {
            $supplier->district = $supplierData->district;
        }
        if ($supplierData->supplierType) {
            $supplier->supplier_type = $supplierData->supplierType;
        }

        $supplier->save();

        return $supplier->id;
    }
}
