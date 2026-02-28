<?php

declare(strict_types=1);

namespace Modules\V1\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\V1\Admin\Models\Admin;

/**
 * @OA\Schema(
 *     schema="AdminUpdateRequest",
 *     title="User Update Request",
 *     description="Request schema for updating a user (admin or regular user)",
 *     type="object",
 *     required={"firstName", "username", "email", "role"},
 *
 *     @OA\Property(property="firstName", type="string", maxLength=255, example="John", description="First name"),
 *     @OA\Property(property="lastName", type="string", maxLength=255, example="Doe", description="Last name (optional)"),
 *     @OA\Property(property="username", type="string", maxLength=255, example="johndoe", description="Username"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Email address"),
 *     @OA\Property(property="password", type="string", minLength=8, example="password123", description="Password (optional for update)"),
 *     @OA\Property(
 *         property="role",
 *         type="string",
 *         enum={"SUPER_ADMIN", "ADMIN_PEMASOK", "KEUANGAN", "PEMASOK", "KOPERASI", "DAPUR"},
 *         example="PEMASOK",
 *         description="Role to assign to the user"
 *     ),
 *     @OA\Property(property="supplier_id", type="string", format="uuid", example="uuid", description="Supplier ID (optional - if not provided, will create new supplier)"),
 *     @OA\Property(property="dapur_id", type="string", format="uuid", example="uuid", description="Dapur ID (optional - if not provided, will create new dapur)"),
 *     @OA\Property(
 *         property="supplier_data",
 *         type="object",
 *         nullable=true,
 *         description="Supplier data (required for PEMASOK/ADMIN_PEMASOK role if supplier_id not provided)",
 *
 *         @OA\Property(property="name", type="string", example="PT Beras Jaya Makmur"),
 *         @OA\Property(property="contact_person", type="string", example="Budi Santoso"),
 *         @OA\Property(property="phone", type="string", example="081234567890"),
 *         @OA\Property(property="email", type="string", format="email", example="supplier@example.com"),
 *         @OA\Property(property="address", type="string", example="Jl. Raya Bogor KM 25")
 *     ),
 *     @OA\Property(
 *         property="dapur_data",
 *         type="object",
 *         nullable=true,
 *         description="Dapur data (required for DAPUR role if dapur_id not provided)",
 *
 *         @OA\Property(property="name", type="string", example="Dapur Pusat A"),
 *         @OA\Property(property="location", type="string", example="Gedung Utama Lt. 1"),
 *         @OA\Property(property="pic_name", type="string", example="Siti Aminah"),
 *         @OA\Property(property="pic_phone", type="string", example="081112223334")
 *     )
 * )
 */
final class AdminUpdateRequest extends FormRequest
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
        $id = $this->route('id');

        return [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['nullable', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('system_users', 'username')->ignore($id),
                Rule::unique('users', 'username')->ignore($id),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('system_users', 'email')->ignore($id),
                Rule::unique('users', 'email')->ignore($id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
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
            'dapur_data.name' => ['required_with:dapur_data', 'string', 'max:255'],
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
            $userId = $this->route('id');

            // If role is PEMASOK or ADMIN_PEMASOK and supplier data is provided
            if (in_array($role, ['PEMASOK', 'ADMIN_PEMASOK'])) {
                $name = $this->input('supplier_data.name') ?? $this->input('supplierNama');

                // Get sequence data
                $category = $this->input('supplier_data.supplier_category') ?? $this->input('supplierKategori');
                $companyCode = $this->input('supplier_data.supplier_company_code') ?? $this->input('supplierKodePerusahaan');
                $location = $this->input('supplier_data.supplier_location') ?? $this->input('supplierLokasi');
                $year = $this->input('supplier_data.supplier_year') ?? $this->input('supplierTahun');
                $sequence = $this->input('supplier_data.supplier_sequence') ?? $this->input('supplierUrut');

                // Check if supplier name is being updated
                if (!empty($name)) {
                    // Get current user's supplier_id
                    $user = \Modules\V1\Admin\Models\Admin::find($userId);
                    if (!$user) {
                        $user = \Modules\V1\User\Models\User::find($userId);
                    }

                    if ($user && $user->supplier_id) {
                        // Check if new name is different from current
                        $currentSupplier = \Modules\V1\Supplier\Models\Supplier::find($user->supplier_id);
                        if ($currentSupplier && $currentSupplier->name !== $name) {
                            // Check if name already exists in other suppliers
                            $existingSupplier = \Modules\V1\Supplier\Models\Supplier::where('name', $name)
                                ->where('id', '!=', $user->supplier_id)
                                ->first();
                            if ($existingSupplier) {
                                $validator->errors()->add('supplierNama', 'Supplier name already exists. Please use a different name.');
                            }
                        }
                    } else {
                        // Creating new supplier, check if name exists
                        $existingSupplier = \Modules\V1\Supplier\Models\Supplier::where('name', $name)->first();
                        if ($existingSupplier) {
                            $validator->errors()->add('supplierNama', 'Supplier name already exists. Please use a different name.');
                        }
                    }
                }

                // Validate sequence number if provided
                if (!empty($sequence) && !empty($category) && !empty($location) && !empty($year)) {
                    // Validate sequence format (exactly 3 digits)
                    if (!preg_match('/^\d{3}$/', $sequence)) {
                        $validator->errors()->add('supplierUrut', 'Sequence must be 3 digits (e.g., 001, 002, 003).');
                    } else {
                        // Get current user's supplier to check if sequence is changing
                        $user = \Modules\V1\Admin\Models\Admin::find($userId);
                        if (!$user) {
                            $user = \Modules\V1\User\Models\User::find($userId);
                        }

                        $currentSupplier = null;
                        $currentSequence = null;
                        $isSequenceChanging = false;

                        if ($user && $user->supplier_id) {
                            $currentSupplier = \Modules\V1\Supplier\Models\Supplier::find($user->supplier_id);
                            if ($currentSupplier) {
                                // Extract current sequence from supplier code
                                $parts = explode('-', $currentSupplier->code);
                                $currentSequence = $parts[5] ?? null;
                                $isSequenceChanging = ($currentSequence !== $sequence);
                            }
                        }

                        // Build the full supplier code
                        $expectedCode = 'SUP-' . $category . '-' . $companyCode . '-' . $location . '-' . $year . '-' . $sequence;

                        // Check for duplicate sequence - GLOBAL sequence for category + location + year
                        $prefix = 'SUP-' . $category . '%-' . $location . '-' . $year . '-' . $sequence;
                        $query = \Modules\V1\Supplier\Models\Supplier::withTrashed()
                            ->where('code', 'like', $prefix);

                        // Exclude current supplier from duplicate check
                        if ($user && $user->supplier_id) {
                            $query->where('id', '!=', $user->supplier_id);
                        }

                        $existingSupplier = $query->first();

                        if ($existingSupplier) {
                            $validator->errors()->add('supplierUrut', "Sequence {$sequence} is already used for category {$category} in location {$location} year {$year}.");
                        } else {
                            // Validate sequence is in order - GLOBAL for category + location + year
                            // Skip validation if sequence is not changing
                            if (!$currentSupplier || $isSequenceChanging) {
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
            }

            // Validate DAPUR role dapur data
            if ($role === 'DAPUR') {
                $dapurName = $this->input('dapur_data.name') ?? $this->input('dapurNama');
                $dapurId = $this->input('dapur_id');

                // Get dapur sequence data
                $fullDapurCode = $this->input('dapur_data.dapur_code') ?? $this->input('dapurKode');
                $dapurZona = $this->input('dapur_data.dapur_zona') ?? $this->input('dapurZona');
                $dapurTahun = $this->input('dapur_data.dapur_year') ?? $this->input('dapurTahun');
                $dapurUrut = $this->input('dapur_data.dapur_sequence') ?? $this->input('dapurUrut');

                // Only validate dapur name if dapur_id is not being used
                if (empty($dapurId)) {
                    if (!empty($dapurName)) {
                        // Get current user's dapur_id
                        $user = \Modules\V1\Admin\Models\Admin::find($userId);
                        if (!$user) {
                            $user = \Modules\V1\User\Models\User::find($userId);
                        }

                        if ($user && $user->dapur_id) {
                            // Check if new name is different from current
                            $currentDapur = \Modules\V1\Kitchen\Models\Dapur::find($user->dapur_id);
                            if ($currentDapur && $currentDapur->name !== $dapurName) {
                                // Check if name already exists in other dapurs
                                $existingDapur = \Modules\V1\Kitchen\Models\Dapur::where('name', $dapurName)
                                    ->where('id', '!=', $user->dapur_id)
                                    ->first();
                                if ($existingDapur) {
                                    $validator->errors()->add('dapurNama', 'Dapur name already exists. Please use a different name.');
                                }
                            }
                        } else {
                            // Creating new dapur, check if name exists
                            $existingDapur = \Modules\V1\Kitchen\Models\Dapur::where('name', $dapurName)->first();
                            if ($existingDapur) {
                                $validator->errors()->add('dapurNama', 'Dapur name already exists. Please use a different name.');
                            }
                        }
                    }

                    // Validate dapur sequence number if provided
                    if (!empty($dapurUrut) && !empty($dapurZona) && !empty($dapurTahun)) {
                        // Validate sequence format (3 digits)
                        if (!preg_match('/^\d{3}$/', $dapurUrut)) {
                            $validator->errors()->add('dapurUrut', 'Sequence must be 3 digits (e.g., 001, 002, 003).');
                        } else {
                            // Get current user's dapur to check if sequence is changing
                            $user = \Modules\V1\Admin\Models\Admin::find($userId);
                            if (!$user) {
                                $user = \Modules\V1\User\Models\User::find($userId);
                            }

                            $currentDapur = null;
                            $currentSequence = null;
                            $isSequenceChanging = false;

                            if ($user && $user->dapur_id) {
                                $currentDapur = \Modules\V1\Kitchen\Models\Dapur::find($user->dapur_id);
                                if ($currentDapur) {
                                    // Extract current sequence from dapur code
                                    $parts = explode('-', $currentDapur->code);
                                    $currentSequence = $parts[4] ?? null;
                                    $isSequenceChanging = ($currentSequence !== $dapurUrut);
                                }
                            }

                            // Build the full dapur code
                            $expectedCode = 'DPR-' . $fullDapurCode . '-' . $dapurZona . '-' . $dapurTahun . '-' . $dapurUrut;

                            // Check for duplicate sequence - GLOBAL sequence for zona + tahun
                            $prefix = 'DPR-%-' . $dapurZona . '-' . $dapurTahun . '-' . $dapurUrut;
                            $query = \Modules\V1\Kitchen\Models\Dapur::withTrashed()
                                ->where('code', 'like', $prefix);

                            // Exclude current dapur from duplicate check
                            if ($user && $user->dapur_id) {
                                $query->where('id', '!=', $user->dapur_id);
                            }

                            $existingDapur = $query->first();

                            if ($existingDapur) {
                                $validator->errors()->add('dapurUrut', "Sequence {$dapurUrut} is already used for zona {$dapurZona} year {$dapurTahun}.");
                            } else {
                                // Validate sequence is in order - GLOBAL for zona + tahun
                                // Skip validation if sequence is not changing
                                if (!$currentDapur || $isSequenceChanging) {
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
                }
            }
        });
    }
}
