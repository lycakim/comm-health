<?php

namespace App\Services;

use App\Exceptions\DuplicatePatientException;
use App\Models\Patient;
use App\Models\Category;
use App\Models\Barangay;
use App\Models\Purok;
use App\Models\Occupation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class PatientImportService
{
    /**
     * Validate and import patients from CSV data
     */
    public function importPatients(array $rows): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because index starts at 0 and we skip header
            
            try {
                $validated = $this->validateRow($row, $rowNumber);
                $this->createPatient($validated);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'row' => $rowNumber,
                    'message' => $e->getMessage(),
                    'data' => $row,
                ];
            }
        }

        return $results;
    }

    /**
     * Validate a single row of data
     */
    protected function validateRow(array $row, int $rowNumber): array
    {
        // Normalize date format before validation (accept yyyy-mm-dd or yyyy/mm/dd)
        // Also handle Excel datetime objects
        if (isset($row['birth_date']) && !empty($row['birth_date'])) {
            // If it's a Carbon instance or DateTime object, convert to string
            if ($row['birth_date'] instanceof \DateTime || $row['birth_date'] instanceof \Carbon\Carbon) {
                $row['birth_date'] = $row['birth_date']->format('Y-m-d');
            } else {
                // Convert string dates (handle both / and - separators)
                $row['birth_date'] = str_replace('/', '-', (string)$row['birth_date']);
                // Extract just the date part if it's a datetime string
                if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $row['birth_date'], $matches)) {
                    $row['birth_date'] = $matches[1];
                }
            }
        }
        
        // Normalize contact_number to string before validation (Excel may read as numeric)
        if (isset($row['contact_number']) && !empty($row['contact_number'])) {
            $row['contact_number'] = (string) $row['contact_number'];
        }

        // Normalize Y/N to boolean for household census flags (Excel uses Y/N)
        foreach (['indigent', 'pwd', 'renter', 'solo_parent', 'senior_citizen'] as $flag) {
            if (!array_key_exists($flag, $row)) {
                continue;
            }
            $v = $row[$flag];
            if (is_bool($v)) {
                $row[$flag] = $v;
            } else {
                $s = strtoupper(trim((string) $v));
                $row[$flag] = in_array($s, ['Y', 'YES', '1', 'TRUE'], true);
            }
        }

        // Coerce numeric fields so validation never blocks saving (no restriction)
        foreach (['sugar_level', 'height', 'weight', 'age'] as $numericKey) {
            if (!array_key_exists($numericKey, $row)) {
                continue;
            }
            $v = $row[$numericKey];
            if ($v === null || $v === '') {
                $row[$numericKey] = null;
            } elseif (!is_numeric($v)) {
                $row[$numericKey] = null;
            } else {
                $num = (float) $v;
                if ($numericKey === 'age') {
                    $row[$numericKey] = ($num >= 0 && $num <= 150) ? (int) $num : null;
                } else {
                    $row[$numericKey] = $v;
                }
            }
        }

        // No restriction: accept all fields as optional; coerce invalid values so every row can be saved.
        $validator = Validator::make($row, [
            'first_name'            => ['nullable', 'string', 'max:255'],
            'middle_name'           => ['nullable', 'string', 'max:255'],
            'last_name'             => ['nullable', 'string', 'max:255'],
            'suffix'                => ['nullable', 'string', 'max:10'],
            'birth_date'            => ['nullable'], // any value accepted; parsed or defaulted below
            'age'                   => ['nullable', 'integer', 'min:0', 'max:150'],
            'sex'                   => ['nullable', 'string', 'max:255'], // any value; coerced to male/female below
            'civil_status'          => ['nullable', 'string', 'max:255'],
            'contact_number'        => ['nullable', 'string'],
            'purok'                 => ['nullable', 'string'],
            'relationship_to_hh'    => ['nullable', 'string'],
            'category'              => ['nullable', 'string'],
            'occupation'            => ['nullable', 'string'],
            'blood_pressure'        => ['nullable', 'string'],
            'sugar_level'           => ['nullable', 'numeric'],
            'height'                => ['nullable', 'numeric', 'min:0'],
            'weight'                => ['nullable', 'numeric', 'min:0'],
            'place_of_birth'        => ['nullable', 'string'],
            'educational_attainment' => ['nullable', 'string'],
            'birth_order'           => ['nullable', 'string', 'max:50'],
            'blood_type'            => ['nullable', 'string', 'max:10'],
            'indigent'              => ['nullable', 'boolean'],
            'pwd'                   => ['nullable', 'boolean'],
            'renter'                => ['nullable', 'boolean'],
            'solo_parent'           => ['nullable', 'boolean'],
            'senior_citizen'        => ['nullable', 'boolean'],
            'household_no'          => ['nullable', 'string', 'max:50'],
            'precinct_no'           => ['nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            throw new \Exception('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        $validated = $validator->validated();

        // Defaults for DB-required fields so every row is accepted and created (no restriction)
        $validated['first_name'] = trim((string) ($validated['first_name'] ?? '')) ?: 'N/A';
        $validated['last_name'] = trim((string) ($validated['last_name'] ?? '')) ?: 'N/A';
        $validated['middle_name'] = trim((string) ($validated['middle_name'] ?? '')) ?: null;
        $validated['sex'] = in_array($validated['sex'] ?? null, ['male', 'female'], true) ? $validated['sex'] : 'male';
        $validated['civil_status'] = trim((string) ($validated['civil_status'] ?? '')) ?: 'Single';

        // Birth date: required by DB; use parsed value, or derive from age when provided, or default when missing/invalid
        if (!empty($validated['birth_date'])) {
            try {
                $validated['birth_date'] = Carbon::parse($validated['birth_date'])->format('Y-m-d');
            } catch (\Exception $e) {
                $validated['birth_date'] = '2000-01-01';
            }
        } elseif (!empty($validated['age']) && is_numeric($validated['age'])) {
            $age = (int) $validated['age'];
            $age = max(0, min(150, $age));
            $validated['birth_date'] = Carbon::today()->subYears($age)->format('Y-m-d');
        } else {
            $validated['birth_date'] = '2000-01-01';
        }

        // Normalize contact number (only if not empty) - always treat as string
        if (!empty($validated['contact_number'])) {
            // Ensure it's a string (Excel might read it as numeric)
            $contactNumber = (string) $validated['contact_number'];
            $validated['contact_number'] = $this->normalizeContactNumber($contactNumber);
        } else {
            $validated['contact_number'] = null;
        }

        // Use current user's barangay_id when available; otherwise resolve from the "Brgy." column in the file
        $userBarangayId = Auth::user()->barangay_id;
        if ($userBarangayId) {
            $validated['barangay_id'] = $userBarangayId;
        } else {
            // MHO / Admin: try to match the Brgy. value from the row to a barangay in the DB
            $brgyName = trim((string) ($row['barangay'] ?? $row['Brgy.'] ?? $row['brgy'] ?? $row['brgy.'] ?? $row['Brgy'] ?? ''));
            if ($brgyName !== '') {
                $barangay = Barangay::whereRaw('UPPER(name) = ?', [strtoupper($brgyName)])->first();
                $validated['barangay_id'] = $barangay?->id ?? null;
            } else {
                $validated['barangay_id'] = null;
            }
        }

        // Handle purok: find by name in current user's barangay, or create new if not null (same barangay only)
        if (!empty($validated['purok'])) {
            $purokName  = trim($validated['purok']);
            $barangayId = $validated['barangay_id'];

            $purok = Purok::whereRaw('BINARY `name` = ?', [$purokName])
                ->where('barangay_id', $barangayId)
                ->first();

            if ($purok) {
                $validated['purok_id'] = $purok->id;
            } else {
                // Create new purok in current user's barangay and use its id
                $purok = Purok::create([
                    'name'        => $purokName,
                    'barangay_id' => $barangayId,
                    'is_active'   => true,
                ]);
                $validated['purok_id'] = $purok->id;
            }
            unset($validated['purok']);
        } else {
            $validated['purok_id'] = null;
        }

        // Handle occupation string to id, or create if not exists (optional)
        if (!empty($validated['occupation'])) {
            $occupationName = trim($validated['occupation']);
            $occupation = Occupation::whereRaw('BINARY `name` = ?', [$occupationName])->first();

            if (!$occupation) {
                $occupation = Occupation::create(['name' => $occupationName]);
            }
            $validated['occupation_id'] = $occupation->id;
            unset($validated['occupation']);
        } else {
            $validated['occupation_id'] = null;
            unset($validated['occupation']);
        }

        // Calculate age first (needed for category auto-assignment)
        if (!empty($validated['birth_date'])) {
            $validated['age'] = Carbon::parse($validated['birth_date'])->age;
        }

        // Handle category string to id - don't create new, leave blank if not found
        // If category is empty, base it on birthdate. If both missing, leave null.
        if (!empty($validated['category'])) {
            $categoryName = trim($validated['category']);
            $category = Category::whereRaw('BINARY `name` = ?', [$categoryName])->first();

            if ($category) {
                $validated['category_id'] = $category->id;
            }
            // If category not found, leave blank (don't create)
        }
        
        // If category is empty but birthdate exists, auto-assign based on age
        if (empty($validated['category_id']) && !empty($validated['birth_date']) && isset($validated['age'])) {
            $validated['category_id'] = $this->getCategoryIdByAge($validated['age']);
        }
        
        // If both category and birthdate are missing, leave null (already handled above)
        
        // Handle blood pressure - if empty, use normal (120/80)
        if (empty($validated['blood_pressure'])) {
            $validated['blood_pressure'] = '120/80';
        } else {
            // Validate format if provided
            $bp = trim($validated['blood_pressure']);
            if (!preg_match('/^\d{2,3}\/\d{2,3}$/', $bp)) {
                // If format is invalid, use normal
                $validated['blood_pressure'] = '120/80';
            }
        }

        // Map Excel "Relationship to HH" to system relationship_to_head_of_family
        if (!empty($validated['relationship_to_hh'])) {
            $validated['relationship_to_head_of_family'] = self::mapRelationshipToHhToSystem(
                trim((string) $validated['relationship_to_hh'])
            );
        }
        unset($validated['relationship_to_hh']);

        // Always remove 'category' and other non-database keys to prevent SQL errors
        // These are converted to their ID equivalents above
        unset($validated['category'], $validated['purok'], $validated['occupation'], $validated['barangay']);

        // Final safety check: remove any keys that don't exist as columns in the patients table
        // This prevents SQL errors if any unexpected keys slip through
        $allowedKeys = [
            'first_name', 'last_name', 'middle_name', 'suffix', 'relationship_to_head_of_family',
            'relationship_to_head_of_family_other', 'place_of_birth', 'birth_date', 'age', 'sex',
            'civil_status', 'educational_attainment', 'contact_number', 'barangay_id', 'purok_id',
            'category_id', 'occupation_id', 'household_head_id', 'pregnant', 'weeks_pregnant', 'months_pregnant',
            'current_family_planning_method', 'family_monthly_income', 'ip', 'ip_type',
            'no_of_house', 'with_fence', 'house_type', 'blood_pressure', 'sugar_level',
            'height', 'weight', 'trained_for_first_aid', 'bmi', 'bmi_category',
            'health_statuses', 'medication_maintenance', 'water_supply_sources',
            'toilet_types', 'drainage_disposals', 'livestock', 'user_id', 'account_user_id',
            'birth_order', 'blood_type', 'indigent', 'pwd', 'renter', 'solo_parent', 'senior_citizen',
            'household_no', 'precinct_no',
        ];
        
        $validated = array_intersect_key($validated, array_flip($allowedKeys));

        return $validated;
    }

    /**
     * Create a patient from validated data
     */
    protected function createPatient(array $data): Patient
    {
        // Check for duplicate within the same barangay only (to avoid cross-barangay false positives)
        $duplicateQuery = Patient::where('first_name', $data['first_name'])
            ->where('last_name', $data['last_name'])
            ->where('middle_name', $data['middle_name'] ?? null)
            ->where('suffix', $data['suffix'] ?? null);

        if (!empty($data['barangay_id'])) {
            $duplicateQuery->where('barangay_id', $data['barangay_id']);
        }

        $existing = $duplicateQuery->first();

        if ($existing) {
            throw new DuplicatePatientException('Patient with same name already exists', $existing);
        }

        $data['user_id'] = Auth::id();

        return Patient::create($data);
    }

    /**
     * Normalize contact number to proper mobile number format (09XXXXXXXXX)
     * Always returns as string. Handles various formats including (9123456789)
     */
    protected function normalizeContactNumber(?string $contact): ?string
    {
        if (empty($contact)) {
            return null;
        }
        
        // Ensure it's a string (handle numeric values from Excel)
        $contact = (string) $contact;
        
        // Remove all non-digit characters except +
        $clean = preg_replace('/[^\d+]/', '', $contact);

        if (empty($clean)) {
            return null;
        }

        // Handle +63 format (international)
        if (str_starts_with($clean, '+63')) {
            $clean = '63' . substr($clean, 3);
        }
        
        // Remove + if present
        $clean = str_replace('+', '', $clean);

        // Handle 63XXXXXXXXXX format (international without +, 12 digits)
        if (str_starts_with($clean, '63') && strlen($clean) == 12) {
            // Convert to local format: 09XXXXXXXXX
            return '0' . substr($clean, 2);
        }

        // Handle 09XXXXXXXXX format (local, 11 digits) - keep as is
        if (str_starts_with($clean, '09') && strlen($clean) == 11) {
            return $clean;
        }

        // Handle 9XXXXXXXXX format (10 digits starting with 9) - prepend 0
        // This handles (9123456789) format after removing parentheses
        if (str_starts_with($clean, '9') && strlen($clean) == 10) {
            return '0' . $clean;
        }

        // Handle 0XXXXXXXXX format (11 digits starting with 0) - keep as is if valid
        if (str_starts_with($clean, '0') && strlen($clean) == 11) {
            return $clean;
        }

        // For any other format, try to normalize to 09XXXXXXXXX if it's 10 digits
        if (strlen($clean) == 10 && is_numeric($clean)) {
            return '0' . $clean;
        }

        // Return as string (preserve original if can't normalize, but ensure it's a string)
        return (string) $clean;
    }

    /**
     * Get category ID based on age. Uses categories' age_min/age_max (dynamic).
     */
    protected function getCategoryIdByAge(int $age): ?int
    {
        $category = Category::findByAge($age);
        return $category?->id;
    }

    /**
     * Map Excel "Relationship to HH" values to system relationship_to_head_of_family values.
     * Keys are case-insensitive (Excel may use HH, SON, SPOUSE, etc.).
     */
    public static function mapRelationshipToHhToSystem(string $excelValue): ?string
    {
        $map = [
            'hh' => 'Household-Head',
            'head' => 'Household-Head',
            'household head' => 'Household-Head',
            'household head of family' => 'Household-Head',
            'spouse' => 'Spouse',
            'wife' => 'Spouse',
            'husband' => 'Spouse',
            'commonlaw partner' => 'Spouse',
            'common-law partner' => 'Spouse',
            'live-in partner' => 'Spouse',
            'live in partner' => 'Spouse',
            'son' => 'Child',
            'daughter' => 'Child',
            'child' => 'Child',
            'brother' => 'Sibling',
            'sister' => 'Sibling',
            'sibling' => 'Sibling',
            'father' => 'Parent',
            'mother' => 'Parent',
            'parent' => 'Parent',
            'grandparent' => 'Grandparent',
            'grandfather' => 'Grandparent',
            'grandmother' => 'Grandparent',
            'grandchild' => 'Grandchild',
            'grandson' => 'Grandchild',
            'granddaughter' => 'Grandchild',
            'son-in-law' => 'Son-in-Law',
            'son in law' => 'Son-in-Law',
            'daughter-in-law' => 'Daughter-in-Law',
            'daughter in law' => 'Daughter-in-Law',
            'father-in-law' => 'Parent-in-Law',
            'father in law' => 'Parent-in-Law',
            'mother-in-law' => 'Parent-in-Law',
            'mother in law' => 'Parent-in-Law',
            'parent-in-law' => 'Parent-in-Law',
            'parent in law' => 'Parent-in-Law',
            'brother-in-law' => 'Sibling-in-Law',
            'brother in law' => 'Sibling-in-Law',
            'sister-in-law' => 'Sibling-in-Law',
            'sister in law' => 'Sibling-in-Law',
            'sibling-in-law' => 'Sibling-in-Law',
            'sibling in law' => 'Sibling-in-Law',
            'co-wife' => 'Co-Wife',
            'co wife' => 'Co-Wife',
            'relative' => 'Relative',
            'boarder' => 'Boarder',
            'helper' => 'Helper',
            'househelper' => 'Helper',
            'house helper' => 'Helper',
        ];
        $key = strtolower(trim($excelValue));

        // If a known mapping exists, use it; otherwise fall back to saving the raw value as-is
        // (title-cased) so no relationship data is ever lost during import.
        return $map[$key] ?? ucfirst(strtolower(trim($excelValue)));
    }

    /**
     * Get template headers for CSV (matches Excel template structure).
     * Order and names match the downloadable Excel template so CSV and Excel imports are consistent.
     * Barangay (Brgy.) is included for reference but import uses current user's barangay_id.
     */
    public static function getTemplateHeaders(): array
    {
        return [
            'Last Name',
            'First Name',
            'Middle Name',
            'Ext.',
            'Purok',
            'Brgy.',
            'Relationship to HH',
            'Household Head',
            'Date of Birth',
            'Birth Order (ika pila nga anak)',
            'Age',
            'Gender',
            'Contact Number',
            'Occupation',
            'Blood Type',
            'Indigent',
            'PWD',
            'RENTER',
            'SOLO PARENT',
            'SEÑIOR CITIZEN',
            'HOUSEHOLD NO.',
            'PRECINCT NO.',
        ];
    }
}