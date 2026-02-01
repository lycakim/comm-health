<?php

namespace App\Services;

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
        
        // Setup validation rules - only required: first_name, middle_name, last_name, sex, civil_status, birth_date
        $validator = Validator::make($row, [
            'first_name'            => ['required', 'regex:/^[A-Za-z\s\.\-]+$/', 'max:255'],
            'middle_name'           => ['required', 'regex:/^[A-Za-z\s\.\-]+$/', 'max:255'],
            'last_name'             => ['required', 'regex:/^[A-Za-z\s\.\-]+$/', 'max:255'],
            'suffix'                => ['nullable', 'regex:/^[A-Za-z0-9\.\s]+$/', 'max:10'],
            'birth_date'            => ['required', 'date', 'before_or_equal:today'],
            'sex'                   => 'required|in:male,female',
            'civil_status'          => ['required', 'string', 'regex:/^[A-Za-z\s\.\-]+$/'],
            'contact_number'        => ['nullable', 'string'], // Accept any string format, normalize will handle it
            'purok'                 => ['nullable', 'string', 'regex:/^[A-Za-z0-9\s\.\-]*$/'],
            'category'              => ['nullable', 'string', 'regex:/^[A-Za-z\s\.\-]*$/'],
            'occupation'            => ['nullable', 'string', 'regex:/^[A-Za-z\s\.\-]*$/'],
            'blood_pressure'        => ['nullable', 'string'],
            'sugar_level'           => ['nullable', 'numeric'],
            'height'                => ['nullable', 'numeric', 'min:0'],
            'weight'                => ['nullable', 'numeric', 'min:0'],
            'place_of_birth'        => ['nullable', 'string'],
            'educational_attainment' => ['nullable', 'string'],
        ], [
            'birth_date.date' => 'The birth date must be a valid date in format YYYY-MM-DD or YYYY/MM/DD.',
        ]);

        if ($validator->fails()) {
            throw new \Exception('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        $validated = $validator->validated();
        
        // Ensure birth_date is in Y-m-d format for database
        if (isset($validated['birth_date'])) {
            try {
                $validated['birth_date'] = Carbon::parse($validated['birth_date'])->format('Y-m-d');
            } catch (\Exception $e) {
                throw new \Exception('Invalid birth date format: ' . $validated['birth_date']);
            }
        }

        // Normalize contact number (only if not empty) - always treat as string
        if (!empty($validated['contact_number'])) {
            // Ensure it's a string (Excel might read it as numeric)
            $contactNumber = (string) $validated['contact_number'];
            $validated['contact_number'] = $this->normalizeContactNumber($contactNumber);
        } else {
            $validated['contact_number'] = null;
        }

        // Always use current user's barangay_id when importing
        $userBarangayId = Auth::user()->barangay_id;
        if (!$userBarangayId) {
            throw new \Exception("Current user has no assigned barangay. Cannot import patients without a barangay assignment.");
        }
        $validated['barangay_id'] = $userBarangayId;

        // Handle purok string to id - save as null if not found (don't create)
        if (!empty($validated['purok'])) {
            $purokName = trim($validated['purok']);
            $barangayId = $validated['barangay_id'];
            
            $purok = Purok::whereRaw('BINARY `name` = ?', [$purokName])
                ->where('barangay_id', $barangayId)
                ->first();

            if ($purok) {
                $validated['purok_id'] = $purok->id;
            } else {
                // If not found, save as null
                $validated['purok_id'] = null;
            }
            unset($validated['purok']);
        } else {
            // If purok not provided, save as null
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

        // Always remove 'category' and other non-database keys to prevent SQL errors
        // These are converted to their ID equivalents above
        unset($validated['category'], $validated['purok'], $validated['occupation']);

        // Final safety check: remove any keys that don't exist as columns in the patients table
        // This prevents SQL errors if any unexpected keys slip through
        $allowedKeys = [
            'first_name', 'last_name', 'middle_name', 'suffix', 'relationship_to_head_of_family',
            'relationship_to_head_of_family_other', 'place_of_birth', 'birth_date', 'age', 'sex',
            'civil_status', 'educational_attainment', 'contact_number', 'barangay_id', 'purok_id',
            'category_id', 'occupation_id', 'pregnant', 'weeks_pregnant', 'months_pregnant',
            'current_family_planning_method', 'family_monthly_income', 'ip', 'ip_type',
            'no_of_house', 'with_fence', 'house_type', 'blood_pressure', 'sugar_level',
            'height', 'weight', 'trained_for_first_aid', 'bmi', 'bmi_category',
            'health_statuses', 'medication_maintenance', 'water_supply_sources',
            'toilet_types', 'drainage_disposals', 'livestock', 'user_id', 'account_user_id'
        ];
        
        $validated = array_intersect_key($validated, array_flip($allowedKeys));

        return $validated;
    }

    /**
     * Create a patient from validated data
     */
    protected function createPatient(array $data): Patient
    {
        // Check for duplicate
        $existing = Patient::where('first_name', $data['first_name'])
            ->where('last_name', $data['last_name'])
            ->where('middle_name', $data['middle_name'] ?? null)
            ->where('suffix', $data['suffix'] ?? null)
            ->first();

        if ($existing) {
            throw new \Exception('Patient with same name already exists');
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
     * Get template headers for CSV
     */
    public static function getTemplateHeaders(): array
    {
        // The column names in the template must reflect that we expect string names, not IDs, for foreigns
        // Barangay is excluded - always uses current user's barangay_id
        return [
            'first_name',
            'middle_name',
            'last_name',
            'suffix',
            'birth_date',
            'sex',
            'civil_status',
            'contact_number',
            'purok',
            'category',
            'occupation',
            'blood_pressure',
            'sugar_level',
            'height',
            'weight',
            'place_of_birth',
            'educational_attainment',
        ];
    }
}