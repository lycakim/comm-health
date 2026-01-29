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
        
        // Setup validation rules for string columns (names, etc., should be strict [letters, space, dot, dash])
        $validator = Validator::make($row, [
            'first_name'            => ['required', 'regex:/^[A-Za-z\s\.\-]+$/', 'max:255'],
            'last_name'             => ['required', 'regex:/^[A-Za-z\s\.\-]+$/', 'max:255'],
            'middle_name'           => ['nullable', 'regex:/^[A-Za-z\s\.\-]+$/', 'max:255'],
            'suffix'                => ['nullable', 'regex:/^[A-Za-z0-9\.\s]+$/', 'max:10'],
            'birth_date'            => ['required', 'date', 'before_or_equal:today'],
            'sex'                   => 'required|in:male,female',
            'civil_status'          => ['required', 'string', 'regex:/^[A-Za-z\s\.\-]+$/'],
            'contact_number'        => ['nullable', 'string'], // Accept any string format, normalize will handle it
            'barangay'              => ['required', 'string', 'regex:/^[A-Za-z\s\.\-]+$/'],
            'purok'                 => ['nullable', 'string', 'regex:/^[A-Za-z0-9\s\.\-]*$/'],
            'category'              => ['nullable', 'string', 'regex:/^[A-Za-z\s\.\-]+$/'],
            'occupation'            => ['nullable', 'string', 'regex:/^[A-Za-z\s\.\-]+$/'],
            'blood_pressure'        => ['nullable', 'string', 'regex:/^\d{2,3}\/\d{2,3}$/'],
            'sugar_level'           => ['nullable', 'numeric'],
            'height'                => ['nullable', 'numeric', 'min:0'],
            'weight'                => ['nullable', 'numeric', 'min:0'],
        ], [
            'barangay.required' => 'Barangay is required and must be a valid name.',
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

        // Normalize contact number (only if not empty)
        if (!empty($validated['contact_number'])) {
            $validated['contact_number'] = $this->normalizeContactNumber($validated['contact_number']);
        } else {
            $validated['contact_number'] = null;
        }

        // Handle barangay string to id, or create if not exists (strict letters)
        if (!empty($validated['barangay'])) {
            $barangayName = trim($validated['barangay']);
            $barangay = Barangay::whereRaw('BINARY `name` = ?', [$barangayName])->first();

            if (!$barangay) {
                // prevent duplicates by strict check (case-sensitive, strict chars already checked by validator)
                $barangay = Barangay::create(['name' => $barangayName]);
            }
            $validated['barangay_id'] = $barangay->id;
            unset($validated['barangay']);
        } else {
            throw new \Exception("Barangay must be provided as a string.");
        }

        // Handle purok string to id, or create if not exists (optional)
        if (!empty($validated['purok'])) {
            $purokName = trim($validated['purok']);
            $purok = Purok::whereRaw('BINARY `name` = ?', [$purokName])
                ->where('barangay_id', $validated['barangay_id'])
                ->first();

            if (!$purok) {
                $purok = Purok::create([
                    'name' => $purokName,
                    'barangay_id' => $validated['barangay_id'],
                ]);
            }
            $validated['purok_id'] = $purok->id;
            unset($validated['purok']);
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

        // Handle category string to id, or use auto-assign logic based on age if empty
        if (!empty($validated['category'])) {
            $categoryName = trim($validated['category']);
            $category = Category::whereRaw('BINARY `name` = ?', [$categoryName])->first();

            if (!$category) {
                $category = Category::create(['name' => $categoryName]);
            }
            $validated['category_id'] = $category->id;
        } elseif (!empty($validated['birth_date']) && isset($validated['age'])) {
            // Auto-assign category based on age if not provided
            $validated['category_id'] = $this->getCategoryIdByAge($validated['age']);
        }
        
        // Always remove 'category' and other non-database keys to prevent SQL errors
        // These are converted to their ID equivalents above
        unset($validated['category'], $validated['barangay'], $validated['purok'], $validated['occupation']);

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
     * Normalize contact number to standard format
     */
    protected function normalizeContactNumber(?string $contact): ?string
    {
        if (empty($contact)) {
            return null;
        }
        
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

        // Handle 63XXXXXXXXXX format (international without +)
        if (str_starts_with($clean, '63') && strlen($clean) == 12) {
            // Convert to local format: 09XXXXXXXXX
            return '0' . substr($clean, 2);
        }

        // Handle 09XXXXXXXXX format (local) - keep as is
        if (str_starts_with($clean, '09') && strlen($clean) == 11) {
            return $clean;
        }

        // Handle 9XXXXXXXXX format - prepend 0
        if (str_starts_with($clean, '9') && strlen($clean) == 10) {
            return '0' . $clean;
        }

        // Return as string (even if invalid format, per doc requirement)
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
        return [
            'first_name',
            'middle_name',
            'last_name',
            'suffix',
            'birth_date',
            'sex',
            'civil_status',
            'contact_number',
            'barangay',
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