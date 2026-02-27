<?php

declare(strict_types=1);

namespace Modules\V1\Supplier\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\V1\Supplier\Models\Supplier;
use Modules\V1\Supplier\Requests\CreateSupplierRequest;
use Modules\V1\Supplier\Requests\UpdateSupplierRequest;
use Modules\V1\Supplier\Resources\SupplierResource;
use Modules\V1\Supplier\Services\SupplierService;
use Shared\Helpers\ResponseHelper;

final class SupplierController
{
    public function __construct(
        private SupplierService $supplierService
    ) {
    }

    /**
     * @OA\Post(
     *      path="/suppliers/list",
     *      summary="Get all suppliers",
     *      description="Returns a paginated list of all suppliers.",
     *      tags={"Suppliers"},
     *
     *      @OA\RequestBody(
     *
     *          @OA\MediaType(
     *              mediaType="application/json",
     *
     *              @OA\Schema(
     *
     *                  @OA\Property(property="pageNumber", type="integer", example=1, description="Page number"),
     *                  @OA\Property(property="pageSize", type="integer", example=10, description="Items per page"),
     *                  @OA\Property(property="sortDir", type="string", enum={"ASC", "DESC"}, example="ASC", description="Sort direction"),
     *                  @OA\Property(property="sortDirColumn", type="string", example="id", description="Column to sort by"),
     *                  @OA\Property(property="search", type="string", example="Beras", description="Global search string")
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *              @OA\Property(property="message", type="string", example="Success")
     *          )
     *      ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function index(Request $request)
    {
        try {
            $pageNumber = $request->input('pageNumber', 1);
            $pageSize = $request->input('pageSize', 15);
            $sortDirColumn = $request->input('sortDirColumn', 'created_at');
            $sortDir = $request->input('sortDir', 'desc');
            $search = $request->input('search', '');

            $suppliers = $this->supplierService->getAll(
                pageNumber: $pageNumber,
                pageSize: $pageSize,
                sortColumn: $sortDirColumn,
                sortDir: $sortDir,
                search: $search
            );

            return ResponseHelper::success(
                data: SupplierResource::collection($suppliers)
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                message: 'Failed to retrieve suppliers',
                exception: $e
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/suppliers/create",
     *     summary="Create a new supplier",
     *     description="Create a new supplier with the provided data",
     *     tags={"Suppliers"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 required={"name", "contact_person", "phone", "email", "address"},
     *
     *                 @OA\Property(property="name", type="string", example="PT Beras Jaya Makmur"),
     *                 @OA\Property(property="contact_person", type="string", example="Budi Santoso"),
     *                 @OA\Property(property="phone", type="string", example="081234567890"),
     *                 @OA\Property(property="email", type="string", format="email", example="budisantoso@berasjaya.co.id"),
     *                 @OA\Property(property="address", type="string", example="Jl. Raya Bogor KM 25, Jakarta Selatan")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Supplier created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Supplier created successfully")
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
    public function store(CreateSupplierRequest $request)
    {
        try {
            $supplier = $this->supplierService->create($request->validated());

            return ResponseHelper::success(
                data: new SupplierResource($supplier),
                message: 'Supplier created successfully',
                status: 201
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                message: 'Failed to create supplier',
                exception: $e
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/suppliers/detail/{id}",
     *     summary="Get supplier detail",
     *     description="Get detailed information about a specific supplier",
     *     tags={"Suppliers"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         in="query",
     *         required=true,
     *         description="Supplier UUID",
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
     *         description="Supplier not found"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function show(string $id)
    {
        try {
            $supplier = $this->supplierService->findById($id);

            return ResponseHelper::success(
                data: new SupplierResource($supplier)
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                message: $e->getMessage(),
                status: $e->getCode() === 404 ? 404 : 500
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/suppliers/update/{id}",
     *     summary="Update supplier",
     *     description="Update an existing supplier's information",
     *     tags={"Suppliers"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Supplier UUID",
     *
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="name", type="string", example="PT Beras Jaya Makmur Updated"),
     *                 @OA\Property(property="contact_person", type="string", example="Budi Santoso"),
     *                 @OA\Property(property="phone", type="string", example="081234567890"),
     *                 @OA\Property(property="email", type="string", format="email", example="budisantoso@berasjaya.co.id"),
     *                 @OA\Property(property="address", type="string", example="Jl. Raya Bogor KM 25, Jakarta Selatan"),
     *                 @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}, example="active")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Supplier updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Supplier updated successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Supplier not found"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function update(UpdateSupplierRequest $request, string $id)
    {
        try {
            $supplier = $this->supplierService->update($id, $request->validated());

            return ResponseHelper::success(
                data: new SupplierResource($supplier),
                message: 'Supplier updated successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                message: $e->getMessage() ?? 'Failed to update supplier',
                status: $e->getCode() === 404 ? 404 : 500
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/suppliers/delete/{id}",
     *     summary="Delete supplier",
     *     description="Soft delete a supplier",
     *     tags={"Suppliers"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Supplier UUID",
     *
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Supplier deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Supplier deleted successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Supplier not found"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function destroy(string $id)
    {
        try {
            $this->supplierService->delete($id);

            return ResponseHelper::success(
                message: 'Supplier deleted successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                message: $e->getMessage() ?? 'Failed to delete supplier',
                status: $e->getCode() === 404 ? 404 : 500
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/suppliers/my-profile",
     *     summary="Update my supplier profile",
     *     description="Update the logged-in supplier user profile (for PEMASOK/ADMIN_PEMASOK roles)",
     *     tags={"Suppliers"},
     *
     *     @OA\RequestBody(
     *         required=false,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="name", type="string", example="PT Beras Jaya Makmur"),
     *                 @OA\Property(property="contact_person", type="string", example="Budi Santoso"),
     *                 @OA\Property(property="phone", type="string", example="081234567890"),
     *                 @OA\Property(property="address", type="string", example="Jl. Raya Bogor KM 25, Jakarta Selatan")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="User not associated with any supplier"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function updateMyProfile(Request $request)
    {
        try {
            // Get authenticated user
            $user = Auth::user();

            // Check if user has supplier_id
            if (!$user->supplier_id) {
                return ResponseHelper::error(
                    message: 'User not associated with any supplier',
                    status: 403
                );
            }

            // Get supplier
            $supplier = Supplier::findOrFail($user->supplier_id);

            // Update only provided fields
            $updateData = [];
            if ($request->filled('name')) {
                $updateData['name'] = $request->name;
            }
            if ($request->filled('contact_person')) {
                $updateData['contact_person'] = $request->contact_person;
            }
            if ($request->filled('phone')) {
                $updateData['phone'] = $request->phone;
            }
            if ($request->filled('address')) {
                $updateData['address'] = $request->address;
            }

            if (!empty($updateData)) {
                $supplier->update($updateData);
            }

            return ResponseHelper::success(
                data: new SupplierResource($supplier),
                message: 'Profile updated successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                message: 'Failed to update profile',
                exception: $e
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/suppliers/my-profile",
     *     summary="Get my supplier profile",
     *     description="Get the logged-in supplier user profile",
     *     tags={"Suppliers"},
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
     *         response=403,
     *         description="User not associated with any supplier"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function getMyProfile()
    {
        try {
            // Get authenticated user
            $user = Auth::user();

            // Check if user has supplier_id
            if (!$user->supplier_id) {
                return ResponseHelper::error(
                    message: 'User not associated with any supplier',
                    status: 403
                );
            }

            // Get supplier
            $supplier = Supplier::findOrFail($user->supplier_id);

            return ResponseHelper::success(
                data: new SupplierResource($supplier)
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                message: 'Failed to retrieve profile',
                exception: $e
            );
        }
    }
}
