<?php

declare(strict_types=1);

namespace Modules\V1\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Modules\V1\Admin\Models\Admin;

/**
 * @OA\Schema(
 *     schema="AdminCreateRequest",
 *     title="User Create Request",
 *     description="Request schema for creating a new user (admin or regular user)",
 *     type="object",
 *     required={"firstName", "username", "email", "password", "role"},
 *
 *     @OA\Property(property="firstName", type="string", maxLength=255, example="John", description="First name"),
 *     @OA\Property(property="lastName", type="string", maxLength=255, example="Doe", description="Last name (optional)"),
 *     @OA\Property(property="username", type="string", maxLength=255, example="johndoe", description="Username"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Email address"),
 *     @OA\Property(property="password", type="string", minLength=8, example="password123", description="Password"),
 *     @OA\Property(
 *         property="role",
 *         type="string",
 *         enum={"SUPER_ADMIN", "ADMIN_PEMASOK", "KEUANGAN", "PEMASOK", "KOPERASI", "DAPUR"},
 *         example="PEMASOK",
 *         description="Role to assign to the user"
 *     ),
 *     @OA\Property(property="supplier_id", type="string", format="uuid", example="uuid", description="Supplier ID (optional - use existing supplier)"),
 *     @OA\Property(property="dapur_id", type="string", format="uuid", example="uuid", description="Dapur ID (optional - use existing dapur)"),
 *     @OA\Property(
 *         property="supplier_data",
 *         type="object",
 *         nullable=true,
 *         description="Supplier data (REQUIRED for PEMASOK/ADMIN_PEMASOK roles if supplier_id not provided. Can send either full supplier_code OR separate components)",
 *
 *         @OA\Property(property="name", type="string", example="PT Sumber Pangan Sejahtera", description="Company name (REQUIRED)"),
 *         @OA\Property(property="contact_person", type="string", example="Budi Santoso", description="Contact person"),
 *         @OA\Property(property="phone", type="string", example="081234567890", description="Phone number"),
 *         @OA\Property(property="email", type="string", format="email", example="supplier@example.com", description="Supplier email"),
 *         @OA\Property(property="address", type="string", example="Jl. Raya Bogor KM 25", description="Supplier address"),
 *         @OA\Property(property="district", type="string", example="Karawang", description="District/Kota (REQUIRED, will be saved to database: Karawang, Jakarta Selatan, etc)"),
 *         @OA\Property(property="supplier_type", type="string", enum={"PKG", "RAW", "EQP", "CON"}, example="RAW", description="Supplier type (REQUIRED): RAW=bahan baku, PKG=packaging, EQP=equipment, CON=consumable"),
 *         @OA\Property(property="supplier_code", type="string", example="SUP-RAW-SPS-KWG-26-0001", description="Full supplier code (OPTIONAL if using separate fields. Format: SUP-{TIPE}-{PERUSAHAAN}-{LOKASI}-{TAHUN}-{URUT})"),
 *         @OA\Property(property="supplier_category", type="string", enum={"RAW", "PKG", "EQP", "CON"}, example="RAW", description="Supplier category (for separate field input)"),
 *         @OA\Property(property="supplier_company_code", type="string", example="SPS", description="Company code (3 uppercase letters, for separate field input)"),
 *         @OA\Property(property="supplier_location", type="string", example="KWG", description="Location code (3 uppercase letters, for separate field input)"),
 *         @OA\Property(property="supplier_year", type="string", example="26", description="Year (2 digits, for separate field input)"),
 *         @OA\Property(property="supplier_sequence", type="string", example="0001", description="Sequence number (4 digits, for separate field input)")
 *     ),
 *     @OA\Property(
 *         property="dapur_data",
 *         type="object",
 *         nullable=true,
 *         description="Dapur data (optional - if not provided and dapur_id not provided, will auto-create with minimal data)",
 *
 *         @OA\Property(property="name", type="string", example="Dapur Pusat A", description="Dapur name"),
 *         @OA\Property(property="location", type="string", example="Gedung Utama Lt. 1", description="Dapur location"),
 *         @OA\Property(property="pic_name", type="string", example="Siti Aminah", description="PIC name"),
 *         @OA\Property(property="pic_phone", type="string", example="081112223334", description="PIC phone")
 *     )
 * )
 */
final class AdminCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:system_users,username', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:system_users,email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'in:SUPER_ADMIN,ADMIN_PEMASOK,KEUANGAN,PEMASOK,KOPERASI,DAPUR'],
            'supplier_id' => ['nullable', 'uuid', 'exists:suppliers,id'],
            'dapur_id' => ['nullable', 'uuid', 'exists:dapurs,id'],
            'supplier_data' => ['nullable', 'array'],
            'supplier_data.name' => ['nullable', 'string', 'max:255'],
            'supplier_data.contact_person' => ['nullable', 'string', 'max:255'],
            'supplier_data.phone' => ['nullable', 'string', 'max:20'],
            'supplier_data.email' => ['nullable', 'email', 'max:255'],
            'supplier_data.address' => ['nullable', 'string'],
            'supplier_data.district' => ['nullable', 'string', 'max:100'],
            'supplier_data.supplier_type' => ['nullable', 'string', 'in:PKG,RAW,EQP,CON,FNB'],
            'supplier_data.supplier_code' => ['nullable', 'string', 'max:50'],
            'supplier_data.supplier_category' => ['nullable', 'string', 'in:RAW,PKG,EQP,CON,FNB'],
            'supplier_data.supplier_company_code' => ['nullable', 'string'],
            'supplier_data.supplier_location' => ['nullable', 'string'],
            'supplier_data.supplier_year' => ['nullable', 'string'],
            'supplier_data.supplier_sequence' => ['nullable', 'string'],
            'dapur_data' => ['nullable', 'array'],
            'dapur_data.name' => ['nullable', 'string', 'max:255'],
            'dapur_data.location' => ['nullable', 'string', 'max:255'],
            'dapur_data.pic_name' => ['nullable', 'string', 'max:255'],
            'dapur_data.pic_phone' => ['nullable', 'string', 'max:20'],
            'dapur_data.dapur_code' => ['nullable', 'string'],
            'dapur_data.dapur_zona' => ['nullable', 'string'],
            'dapur_data.dapur_year' => ['nullable', 'string'],
            'dapur_data.dapur_sequence' => ['nullable', 'string'],
            // Root level fields (from frontend form)
            'supplierKategori' => ['nullable', 'string'],
            'supplierKodePerusahaan' => ['nullable', 'string'],
            'supplierLokasi' => ['nullable', 'string'],
            'supplierTahun' => ['nullable', 'string'],
            'supplierUrut' => ['nullable', 'string'],
            'supplierNama' => ['nullable', 'string'],
            'supplierDistrict' => ['nullable', 'string'],
            'supplierContactPerson' => ['nullable', 'string'],
            'supplierPhone' => ['nullable', 'string'],
            'supplierEmail' => ['nullable', 'string'],
            'supplierAddress' => ['nullable', 'string'],
            // Dapur root level fields (from frontend form)
            'dapurKode' => ['nullable', 'string'],
            'dapurZona' => ['nullable', 'string'],
            'dapurTahun' => ['nullable', 'string'],
            'dapurUrut' => ['nullable', 'string'],
            'dapurNama' => ['nullable', 'string'],
            'dapurLocation' => ['nullable', 'string'],
            'dapurPicName' => ['nullable', 'string'],
            'dapurPicPhone' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'firstName.required' => 'First name is required',
            'username.required' => 'Username is required',
            'username.unique' => 'Username already exists',
            'email.required' => 'Email is required',
            'email.email' => 'Email must be a valid email address',
            'email.unique' => 'Email already exists',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'role.required' => 'Role is required',
            'role.in' => 'Invalid role selected',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $role = $this->input('role');
            $supplierId = $this->input('supplier_id');

            // If role is PEMASOK or ADMIN_PEMASOK and no existing supplier_id is provided
            if (in_array($role, ['PEMASOK', 'ADMIN_PEMASOK']) && empty($supplierId)) {
                // Get values from either nested or root level fields
                $category = $this->input('supplier_data.supplier_category') ?? $this->input('supplierKategori');
                $companyCode = $this->input('supplier_data.supplier_company_code') ?? $this->input('supplierKodePerusahaan');
                $location = $this->input('supplier_data.supplier_location') ?? $this->input('supplierLokasi');
                $year = $this->input('supplier_data.supplier_year') ?? $this->input('supplierTahun');
                $sequence = $this->input('supplier_data.supplier_sequence') ?? $this->input('supplierUrut');
                $name = $this->input('supplier_data.name') ?? $this->input('supplierNama');
                $district = $this->input('supplier_data.district') ?? $this->input('supplierDistrict');

                // Parse full supplier code if provided in format: SUP-{TIPE}-{KODE PERUSAHAAN}-{KODE DAERAH}-{TAHUN}-{NOMOR URUT}
                $fullSupplierCode = $this->input('supplier_data.supplier_code') ?? $this->input('supplierKode');
                if (!empty($fullSupplierCode) && strpos($fullSupplierCode, 'SUP-') === 0 && empty($category)) {
                    $parts = explode('-', $fullSupplierCode);
                    if (count($parts) === 6) {
                        // SUP-{TIPE}-{KODE PERUSAHAAN}-{KODE DAERAH}-{TAHUN}-{NOMOR URUT}
                        $category = $category ?: ($parts[1] ?? '');
                        $companyCode = $companyCode ?: ($parts[2] ?? '');
                        $location = $location ?: ($parts[3] ?? '');
                        $year = $year ?: ($parts[4] ?? '');
                        $sequence = $sequence ?: ($parts[5] ?? '');
                    }
                }

                if (empty($name)) {
                    $validator->errors()->add('supplierNama', 'Supplier name is required.');
                } else {
                    // Check if supplier name already exists
                    $existingSupplier = \Modules\V1\Supplier\Models\Supplier::where('name', $name)->first();
                    if ($existingSupplier) {
                        $validator->errors()->add('supplierNama', 'Supplier name already exists. Please use a different name.');
                    }
                }

                if (empty($district)) {
                    $validator->errors()->add('supplierDistrict', 'Supplier district is required.');
                }

                // Validate supplier email format if provided
                $supplierEmail = $this->input('supplier_data.email') ?? $this->input('supplierEmail');
                if (!empty($supplierEmail) && !filter_var($supplierEmail, FILTER_VALIDATE_EMAIL)) {
                    $validator->errors()->add('supplierEmail', 'Supplier email must be a valid email address.');
                }

                // Validate sequence number
                if (!empty($sequence) && !empty($category) && !empty($location) && !empty($year)) {
                    // Validate sequence format (exactly 3 digits)
                    if (!preg_match('/^\d{3}$/', $sequence)) {
                        $validator->errors()->add('supplierUrut', 'Sequence must be 3 digits (e.g., 001, 002, 003).');
                    } else {
                        // Build the full supplier code
                        $expectedCode = 'SUP-' . $category . '-' . $companyCode . '-' . $location . '-' . $year . '-' . $sequence;

                        // Check for duplicate sequence - GLOBAL sequence for category + location + year
                        $prefix = 'SUP-' . $category . '%-' . $location . '-' . $year . '-' . $sequence;

                        $existingSupplier = \Modules\V1\Supplier\Models\Supplier::withTrashed()
                            ->where('code', 'like', $prefix)
                            ->first();

                        if ($existingSupplier) {
                            $validator->errors()->add('supplierUrut', "Sequence {$sequence} is already used for category {$category} in location {$location} year {$year}.");
                        } else {
                            // Validate sequence is in order - GLOBAL for category + location + year
                            $prefix = 'SUP-' . $category . '-%' . '-' . $location . '-' . $year . '-';

                            $existingCodes = \Modules\V1\Supplier\Models\Supplier::withTrashed()
                                ->where('code', 'like', $prefix . '%')
                                ->pluck('code')
                                ->map(function ($code) {
                                    // Extract sequence from code: SUP-RAW-XXX-KRW-26-001
                                    $parts = explode('-', $code);
                                    return (int) ($parts[5] ?? 0);
                                })
                                ->sort()
                                ->values()
                                ->toArray();

                            $sequenceInt = (int) $sequence;

                            if (empty($existingCodes)) {
                                // First supplier should be 001
                                if ($sequenceInt !== 1) {
                                    $validator->errors()->add('supplierUrut', 'First sequence must be 001.');
                                }
                            } else {
                                // Check if sequence is already used
                                if (in_array($sequenceInt, $existingCodes)) {
                                    $validator->errors()->add('supplierUrut', "Sequence {$sequence} is already used.");
                                } else {
                                    // Find next expected sequence
                                    $maxSequence = max($existingCodes);
                                    $expectedSequence = $maxSequence + 1;

                                    if ($sequenceInt !== $expectedSequence) {
                                        $validator->errors()->add('supplierUrut', "Sequence must be " . str_pad((string)$expectedSequence, 3, '0', STR_PAD_LEFT) . " (next available).");
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Validate DAPUR role dapur data
            $dapurId = $this->input('dapur_id');
            if ($role === 'DAPUR' && empty($dapurId)) {
                $dapurName = $this->input('dapur_data.name') ?? $this->input('dapurNama');

                // Get dapur code - could be full code or separate components
                $fullDapurCode = $this->input('dapur_data.dapur_code') ?? $this->input('dapurKode');
                $dapurZona = $this->input('dapur_data.dapur_zona') ?? $this->input('dapurZona');
                $dapurTahun = $this->input('dapur_data.dapur_year') ?? $this->input('dapurTahun');
                $dapurUrut = $this->input('dapur_data.dapur_sequence') ?? $this->input('dapurUrut');

                // Parse full dapur code if provided in format: DPR-{KODE}-{ZONA}-{TAHUN}-{URUT}
                $dapurCode = $fullDapurCode;
                if (!empty($fullDapurCode) && strpos($fullDapurCode, 'DPR-') === 0) {
                    $parts = explode('-', $fullDapurCode);
                    if (count($parts) === 6) {
                        // DPR-{KODE}-{ZONA}-{TAHUN}-{URUT}
                        $dapurCode = $parts[1] ?? '';
                        $dapurZona = $dapurZona ?: ($parts[2] ?? '');
                        $dapurTahun = $dapurTahun ?: ($parts[3] ?? '');
                        $dapurUrut = $dapurUrut ?: ($parts[4] ?? '');
                    }
                }

                if (!$dapurName || trim($dapurName) === '') {
                    $validator->errors()->add('dapurNama', 'Dapur name is required.');
                } else {
                    // Check if dapur name already exists
                    $existingDapur = \Modules\V1\Kitchen\Models\Dapur::where('name', $dapurName)->first();
                    if ($existingDapur) {
                        $validator->errors()->add('dapurNama', 'Dapur name already exists. Please use a different name.');
                    }
                }

                // Validate dapur sequence number if all components are provided
                if (!empty($dapurUrut) && !empty($dapurZona) && !empty($dapurTahun)) {
                    // Validate sequence format (3 digits)
                    if (!preg_match('/^\d{3}$/', $dapurUrut)) {
                        $validator->errors()->add('dapurUrut', 'Sequence must be 3 digits (e.g., 001, 002, 003).');
                    } else {
                        // Build the full dapur code
                        $expectedCode = 'DPR-' . $dapurCode . '-' . $dapurZona . '-' . $dapurTahun . '-' . $dapurUrut;

                        // Check for duplicate sequence - GLOBAL sequence for zona + tahun
                        $prefix = 'DPR-%-' . $dapurZona . '-' . $dapurTahun . '-' . $dapurUrut;

                        $existingDapur = \Modules\V1\Kitchen\Models\Dapur::withTrashed()
                            ->where('code', 'like', $prefix)
                            ->first();

                        if ($existingDapur) {
                            $validator->errors()->add('dapurUrut', "Sequence {$dapurUrut} is already used for zona {$dapurZona} year {$dapurTahun}.");
                        } else {
                            // Validate sequence is in order - GLOBAL for zona + tahun
                            $prefix = 'DPR-%-' . $dapurZona . '-' . $dapurTahun . '-';

                            $existingCodes = \Modules\V1\Kitchen\Models\Dapur::withTrashed()
                                ->where('code', 'like', $prefix . '%')
                                ->pluck('code')
                                ->map(function ($code) {
                                    // Extract sequence from code: DPR-XXX-Z01-26-001
                                    $parts = explode('-', $code);
                                    return (int) ($parts[4] ?? 0);
                                })
                                ->sort()
                                ->values()
                                ->toArray();

                            $dapurUrutInt = (int) $dapurUrut;

                            if (empty($existingCodes)) {
                                // First dapur should be 001
                                if ($dapurUrutInt !== 1) {
                                    $validator->errors()->add('dapurUrut', 'First sequence must be 001.');
                                }
                            } else {
                                // Check if sequence is already used
                                if (in_array($dapurUrutInt, $existingCodes)) {
                                    $validator->errors()->add('dapurUrut', "Sequence {$dapurUrut} is already used.");
                                } else {
                                    // Find next expected sequence
                                    $maxSequence = max($existingCodes);
                                    $expectedSequence = $maxSequence + 1;

                                    if ($dapurUrutInt !== $expectedSequence) {
                                        $validator->errors()->add('dapurUrut', "Sequence must be " . str_pad((string)$expectedSequence, 3, '0', STR_PAD_LEFT) . " (next available).");
                                    }
                                }
                            }
                        }
                    }
                }
            }
        });
    }
}
