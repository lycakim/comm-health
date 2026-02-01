<?php

namespace App\Imports;

use App\Services\PatientImportService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDateConverter;

class PatientsImport
{
    protected PatientImportService $importService;

    protected array $errors = [];

    protected int $successCount = 0;

    protected int $failedCount = 0;

    protected int $chunkSize = 100;

    public function __construct()
    {
        $this->importService = new PatientImportService();
    }

    /**
     * Import patients from an Excel or CSV file.
     */
    public function import(string $filePath): array
    {
        $this->errors = [];
        $this->successCount = 0;
        $this->failedCount = 0;

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestDataColumn();

        if ($highestRow < 2) {
            return $this->getResults();
        }

        // First row as headers
        $headerRow = $sheet->rangeToArray('A1:' . $highestColumn . '1', null, true, true, false)[0];

        $currentRowNumber = 2;
        for ($rowIndex = 2; $rowIndex <= $highestRow; $rowIndex += $this->chunkSize) {
            $chunkEnd = min($rowIndex + $this->chunkSize - 1, $highestRow);
            $rows = $sheet->rangeToArray(
                'A' . $rowIndex . ':' . $highestColumn . $chunkEnd,
                null,
                true,
                true,
                false
            );

            foreach ($rows as $row) {
                $assoc = [];
                foreach ($headerRow as $i => $key) {
                    $assoc[trim((string) $key)] = $row[$i] ?? null;
                }
                // Skip completely empty rows
                if (array_filter($assoc, fn ($v) => $v !== null && $v !== '') === []) {
                    $currentRowNumber++;
                    continue;
                }
                $this->processRow($assoc, $currentRowNumber);
                $currentRowNumber++;
            }
        }

        return $this->getResults();
    }

    /**
     * Process a single row (normalize, validate, create).
     */
    protected function processRow(array $rowArray, int $rowNumber): void
    {
        try {
            $normalizedRow = $this->normalizeRow($rowArray);

            if (isset($normalizedRow['birth_date']) && ! empty($normalizedRow['birth_date'])) {
                if ($normalizedRow['birth_date'] instanceof \DateTime || $normalizedRow['birth_date'] instanceof \Carbon\Carbon) {
                    $normalizedRow['birth_date'] = $normalizedRow['birth_date']->format('Y-m-d');
                } else {
                    $normalizedRow['birth_date'] = str_replace('/', '-', (string) $normalizedRow['birth_date']);
                    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $normalizedRow['birth_date'], $matches)) {
                        $normalizedRow['birth_date'] = $matches[1];
                    }
                }
            }

            $reflection = new \ReflectionClass($this->importService);
            $validateMethod = $reflection->getMethod('validateRow');
            $validateMethod->setAccessible(true);
            $validated = $validateMethod->invoke($this->importService, $normalizedRow, $rowNumber);

            $createMethod = $reflection->getMethod('createPatient');
            $createMethod->setAccessible(true);
            $createMethod->invoke($this->importService, $validated);

            $this->successCount++;
        } catch (\Exception $e) {
            $this->failedCount++;
            $this->errors[] = [
                'row' => $rowNumber,
                'message' => $e->getMessage(),
                'data' => $rowArray,
            ];
        }
    }

    /**
     * Normalize row keys to match expected format.
     */
    protected function normalizeRow(array $row): array
    {
        $normalized = [];
        $normalizedKeys = [];
        foreach ($row as $key => $value) {
            $normalizedKey = strtolower(str_replace([' ', '-'], '_', trim((string) $key)));
            $normalizedKeys[$normalizedKey] = $key;
        }

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

                    if ($standardKey === 'birth_date' && ! empty($value)) {
                        $value = $this->convertExcelDate($value);
                    }
                    if ($standardKey === 'contact_number' && ! empty($value)) {
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
     * Convert Excel date (serial number or various formats) to Y-m-d string.
     */
    protected function convertExcelDate($value): string
    {
        if ($value instanceof \DateTime || $value instanceof \Carbon\Carbon) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            try {
                $date = ExcelDateConverter::excelToDateTimeObject($value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                return (string) $value;
            }
        }

        if (is_string($value)) {
            $value = str_replace('/', '-', trim($value));
            if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $value, $matches)) {
                return $matches[1];
            }
            try {
                return \Carbon\Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return $value;
            }
        }

        return (string) $value;
    }

    public function getResults(): array
    {
        return [
            'success' => $this->successCount,
            'failed' => $this->failedCount,
            'errors' => $this->errors,
        ];
    }
}
