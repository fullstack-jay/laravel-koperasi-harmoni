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
}
