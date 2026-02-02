<?php

namespace App\Imports;

use App\Services\PatientImportService;

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
     * Import patients from an Excel (.xlsx) or CSV file using vanilla PHP only.
     */
    public function import(string $filePath): array
    {
        $this->errors = [];
        $this->successCount = 0;
        $this->failedCount = 0;

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'csv') {
            $rows = $this->readCsv($filePath);
        } elseif ($ext === 'xlsx') {
            $rows = $this->readXlsx($filePath);
        } else {
            $this->errors[] = ['row' => 0, 'message' => 'Unsupported file type. Use .csv or .xlsx', 'data' => []];
            return $this->getResults();
        }

        if (empty($rows)) {
            return $this->getResults();
        }

        $headerRow = array_map('trim', array_map('strval', $rows[0]));
        $dataRows = array_slice($rows, 1);
        $currentRowNumber = 2;

        foreach ($dataRows as $row) {
            $assoc = [];
            foreach ($headerRow as $i => $key) {
                $assoc[$key] = $row[$i] ?? null;
            }
            if (array_filter($assoc, fn ($v) => $v !== null && $v !== '') === []) {
                $currentRowNumber++;
                continue;
            }
            $this->processRow($assoc, $currentRowNumber);
            $currentRowNumber++;
        }

        return $this->getResults();
    }

    /**
     * Read CSV file with vanilla PHP (fgetcsv).
     */
    protected function readCsv(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [];
        }
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }

    /**
     * Read .xlsx file with vanilla PHP (ZipArchive + SimpleXML). No external package.
     */
    protected function readXlsx(string $filePath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath, \ZipArchive::RDONLY) !== true) {
            return [];
        }

        $sharedStrings = $this->readXlsxSharedStrings($zip);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false || $sharedStrings === null) {
            return [];
        }

        return $this->parseSheetXml($sheetXml, $sharedStrings);
    }

    private const XLSX_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    /**
     * Parse sharedStrings.xml from xlsx.
     */
    protected function readXlsxSharedStrings(\ZipArchive $zip): ?array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }
        $sxe = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOEMPTYTAG);
        if ($sxe === false) {
            return [];
        }
        $si = $sxe->children(self::XLSX_NS)->si ?? $sxe->si;
        if (!isset($si)) {
            return [];
        }
        $list = [];
        foreach ($si as $item) {
            $text = '';
            $itemNs = $item->children(self::XLSX_NS);
            if (isset($item->t)) {
                $text = (string) $item->t;
            } elseif (isset($itemNs->r)) {
                foreach ($itemNs->r as $r) {
                    $text .= (string) ($r->t ?? '');
                }
            } else {
                foreach ($item->r ?? [] as $r) {
                    $text .= (string) ($r->t ?? '');
                }
            }
            $list[] = $text;
        }
        return $list;
    }

    /**
     * Parse sheet XML into rows (array of array of cell values).
     */
    protected function parseSheetXml(string $sheetXml, array $sharedStrings): array
    {
        $sxe = @simplexml_load_string($sheetXml, 'SimpleXMLElement', LIBXML_NOEMPTYTAG);
        if ($sxe === false) {
            return [];
        }
        $sheetData = $sxe->children(self::XLSX_NS)->sheetData ?? $sxe->sheetData;
        $rowElements = $sheetData->children(self::XLSX_NS)->row ?? $sheetData->row ?? [];
        if (empty($rowElements)) {
            return [];
        }

        $rows = [];
        foreach ($rowElements as $row) {
            $rowNum = (int) ($row['r'] ?? count($rows) + 1);
            $cells = [];
            $cellElements = $row->children(self::XLSX_NS)->c ?? $row->c ?? [];
            foreach ($cellElements as $c) {
                $r = (string) $c['r'];
                $colIndex = $this->columnLettersToIndex($r);
                $value = null;
                $vEl = $c->children(self::XLSX_NS)->v ?? $c->v;
                if ($vEl !== null && (string) $vEl !== '') {
                    $v = (string) $vEl;
                    $t = (string) ($c['t'] ?? '');
                    if ($t === 's') {
                        $value = $sharedStrings[(int) $v] ?? '';
                    } elseif ($t === 'd') {
                        $value = $v;
                    } else {
                        $value = is_numeric($v) ? (float) $v : $v;
                    }
                }
                $cells[$colIndex] = $value;
            }
            $rows[$rowNum] = $cells;
        }

        if (empty($rows)) {
            return [];
        }
        $minRow = min(array_keys($rows));
        $maxRow = max(array_keys($rows));
        $maxCol = 0;
        foreach ($rows as $cells) {
            foreach (array_keys($cells) as $c) {
                if ($c > $maxCol) {
                    $maxCol = $c;
                }
            }
        }

        $out = [];
        for ($r = $minRow; $r <= $maxRow; $r++) {
            $cells = $rows[$r] ?? [];
            $line = [];
            for ($c = 0; $c <= $maxCol; $c++) {
                $line[] = $cells[$c] ?? null;
            }
            $out[] = $line;
        }
        return $out;
    }

    /**
     * Convert Excel column letters (e.g. "A", "B", "AA") to 0-based index.
     */
    protected function columnLettersToIndex(string $cellRef): int
    {
        $letters = preg_replace('/[0-9]+/', '', $cellRef);
        $col = 0;
        for ($i = 0; $i < strlen($letters); $i++) {
            $col = $col * 26 + (ord($letters[$i]) - ord('A') + 1);
        }
        return $col > 0 ? $col - 1 : 0;
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
     * Convert Excel date (serial number or string) to Y-m-d using vanilla PHP only.
     */
    protected function convertExcelDate($value): string
    {
        if ($value instanceof \DateTime || $value instanceof \Carbon\Carbon) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            $serial = (float) $value;
            $date = $this->excelSerialToDate($serial);
            return $date ? $date->format('Y-m-d') : (string) $value;
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

    /**
     * Convert Excel serial date (days since 1899-12-30) to DateTime. Vanilla PHP.
     */
    protected function excelSerialToDate(float $serial): ?\DateTime
    {
        $base = new \DateTime('1899-12-30');
        $days = (int) floor($serial);
        if ($days >= 60) {
            $days--; // Excel 1900 leap year bug
        }
        $base->add(new \DateInterval('P' . $days . 'D'));
        $frac = $serial - floor($serial);
        if ($frac > 0) {
            $seconds = (int) round($frac * 86400);
            $base->add(new \DateInterval('PT' . $seconds . 'S'));
        }
        return $base;
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
