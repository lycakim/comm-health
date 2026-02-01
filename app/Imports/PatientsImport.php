<?php

namespace App\Imports;

use App\Services\PatientImportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class PatientsImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected PatientImportService $importService;
    protected array $errors = [];
    protected int $successCount = 0;
    protected int $failedCount = 0;

    public function __construct()
    {
        $this->importService = new PatientImportService();
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because index starts at 0 and we skip header
            
            try {
                // Convert collection row to array
                $rowArray = $row->toArray();
                
                // Normalize the row keys (Excel may have spaces or different casing)
                $normalizedRow = $this->normalizeRow($rowArray);
                
                // Normalize date format if present (accept yyyy-mm-dd or yyyy/mm/dd)
                // Excel may send dates as Carbon instances or datetime strings
                if (isset($normalizedRow['birth_date']) && !empty($normalizedRow['birth_date'])) {
                    // If it's already a Carbon instance or DateTime, convert to string
                    if ($normalizedRow['birth_date'] instanceof \DateTime || $normalizedRow['birth_date'] instanceof \Carbon\Carbon) {
                        $normalizedRow['birth_date'] = $normalizedRow['birth_date']->format('Y-m-d');
                    } else {
                        // Convert string dates (handle both / and - separators)
                        $normalizedRow['birth_date'] = str_replace('/', '-', (string)$normalizedRow['birth_date']);
                        // Extract just the date part if it's a datetime string
                        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $normalizedRow['birth_date'], $matches)) {
                            $normalizedRow['birth_date'] = $matches[1];
                        }
                    }
                }
                
                // Use reflection to access protected methods
                $reflection = new \ReflectionClass($this->importService);
                
                // Call validateRow method
                $validateMethod = $reflection->getMethod('validateRow');
                $validateMethod->setAccessible(true);
                $validated = $validateMethod->invoke($this->importService, $normalizedRow, $rowNumber);
                
                // Call createPatient method
                $createMethod = $reflection->getMethod('createPatient');
                $createMethod->setAccessible(true);
                $createMethod->invoke($this->importService, $validated);
                
                $this->successCount++;
            } catch (\Exception $e) {
                $this->failedCount++;
                $this->errors[] = [
                    'row' => $rowNumber,
                    'message' => $e->getMessage(),
                    'data' => $rowArray ?? $row->toArray(),
                ];
            }
        }
    }

    /**
     * Normalize row keys to match expected format
     * Handles various Excel header formats (spaces, underscores, case variations)
     */
    protected function normalizeRow(array $row): array
    {
        $normalized = [];
        
        // Normalize all keys to lowercase with underscores for comparison
        $normalizedKeys = [];
        foreach ($row as $key => $value) {
            $normalizedKey = strtolower(str_replace([' ', '-'], '_', trim($key)));
            $normalizedKeys[$normalizedKey] = $key; // Store original key
        }
        
        // Map normalized keys to standard keys
        $keyMap = [
            'first_name' => ['first_name', 'firstname'],
            'middle_name' => ['middle_name', 'middlename'],
            'last_name' => ['last_name', 'lastname'],
            'suffix' => ['suffix'],
            'birth_date' => ['birth_date', 'birthdate', 'date_of_birth', 'dateofbirth'],
            'sex' => ['sex', 'gender'],
            'civil_status' => ['civil_status', 'civilstatus'],
            'contact_number' => ['contact_number', 'contactnumber', 'phone', 'phone_number'],
            'purok' => ['purok', 'purok_id', 'purokid'],
            'category' => ['category', 'category_id', 'categoryid'],
            'occupation' => ['occupation', 'occupation_id', 'occupationid'],
            'blood_pressure' => ['blood_pressure', 'bloodpressure'],
            'sugar_level' => ['sugar_level', 'sugarlevel'],
            'height' => ['height'],
            'weight' => ['weight'],
            'place_of_birth' => ['place_of_birth', 'placeofbirth'],
            'educational_attainment' => ['educational_attainment', 'educationalattainment'],
        ];
        
        foreach ($keyMap as $standardKey => $variations) {
            foreach ($variations as $variation) {
                if (isset($normalizedKeys[$variation])) {
                    $originalKey = $normalizedKeys[$variation];
                    $value = $row[$originalKey];
                    
                    // Special handling for birth_date - convert Excel serial number to date
                    if ($standardKey === 'birth_date' && !empty($value)) {
                        $value = $this->convertExcelDate($value);
                    }
                    
                    // Special handling for contact_number - always treat as string
                    if ($standardKey === 'contact_number' && !empty($value)) {
                        // Convert to string to preserve leading zeros and handle numeric values from Excel
                        $value = (string) $value;
                    }
                    
                    $normalized[$standardKey] = $value;
                    break;
                }
            }
        }
        
        return $normalized;
    }

    /**
     * Convert Excel date (serial number or various formats) to Y-m-d string
     */
    protected function convertExcelDate($value): string
    {
        // If already a DateTime/Carbon instance
        if ($value instanceof \DateTime || $value instanceof \Carbon\Carbon) {
            return $value->format('Y-m-d');
        }
        
        // If it's a numeric value (Excel serial date)
        if (is_numeric($value)) {
            // Excel stores dates as days since 1900-01-01 (with some quirks)
            // Use PhpSpreadsheet's date helper
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                // Fallback: treat as string
                return (string)$value;
            }
        }
        
        // If it's a string, try to parse it
        if (is_string($value)) {
            $value = str_replace('/', '-', trim($value));
            
            // Extract date part if it's a datetime string
            if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $value, $matches)) {
                return $matches[1];
            }
            
            // Try parsing with Carbon for other formats
            try {
                return \Carbon\Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return $value; // Return as-is and let validation catch it
            }
        }
        
        return (string)$value;
    }


    /**
     * Chunk size for processing
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Get import results
     */
    public function getResults(): array
    {
        return [
            'success' => $this->successCount,
            'failed' => $this->failedCount,
            'errors' => $this->errors,
        ];
    }
}