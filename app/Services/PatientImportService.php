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
        // Setup validation rules for string columns (names, etc., should be strict [letters, space, dot, dash])
        $validator = Validator::make($row, [
            'first_name'            => ['required', 'regex:/^[A-Za-z\s\.\-]+$/', 'max:255'],
            'last_name'             => ['required', 'regex:/^[A-Za-z\s\.\-]+$/', 'max:255'],
            'middle_name'           => ['nullable', 'regex:/^[A-Za-z\s\.\-]+$/', 'max:255'],
            'suffix'                => ['nullable', 'alpha', 'max:10'],
            'birth_date'            => 'required|date|before_or_equal:today',
            'sex'                   => 'required|in:male,female',
            'civil_status'          => ['required', 'string', 'regex:/^[A-Za-z\s\.\-]+$/'],
            'contact_number'        => ['required', 'string', 'regex:/^(09\d{9}|9\d{9}|639\d{9})$/'],
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
        ]);

        if ($validator->fails()) {
            throw new \Exception('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        $validated = $validator->validated();

        // Normalize contact number
        $validated['contact_number'] = $this->normalizeContactNumber($validated['contact_number']);

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

        // Handle category string to id, or use auto-assign logic if empty
        if (!empty($validated['category'])) {
            $categoryName = trim($validated['category']);
            $category = Category::whereRaw('BINARY `name` = ?', [$categoryName])->first();

            if (!$category) {
                $category = Category::create(['name' => $categoryName]);
            }
            $validated['category_id'] = $category->id;
            unset($validated['category']);
        } elseif (!empty($validated['birth_date'])) {
            // Auto-assign category if not provided
            $age = Carbon::parse($validated['birth_date'])->age;
            $validated['category_id'] = $this->getCategoryIdByAge($age);
        }

        // Calculate age
        if (!empty($validated['birth_date'])) {
            $validated['age'] = Carbon::parse($validated['birth_date'])->age;
        }

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
    protected function normalizeContactNumber(string $contact): string
    {
        $clean = preg_replace('/\D/', '', $contact);

        if (str_starts_with($clean, '09')) {
            return '63' . substr($clean, 1);
        }

        if (str_starts_with($clean, '9')) {
            return '63' . $clean;
        }

        return $clean;
    }

    /**
     * Get category ID based on age
     */
    protected function getCategoryIdByAge(int $age): ?int
    {
        if ($age >= 0 && $age <= 18) {
            return Category::where('name', 'LIKE', '%child%')
                ->orWhere('is_child', true)
                ->value('id');
        } elseif ($age >= 60) {
            return Category::where('name', 'LIKE', '%senior citizen%')
                ->orWhere('name', 'LIKE', '%senior%')
                ->value('id');
        }

        return null;
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