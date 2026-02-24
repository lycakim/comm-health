<?php

namespace App\Imports;

use App\Exceptions\DuplicatePatientException;
use App\Services\PatientImportService;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PatientsImport
{
    protected PatientImportService $importService;

    protected array $errors = [];

    protected int $successCount = 0;

    protected int $failedCount = 0;

    protected int $skippedCount = 0;

    public function __construct()
    {
        $this->importService = new PatientImportService();
    }

    /**
     * Import patients from an Excel (.xlsx) or CSV file using vanilla PHP only.
     */
    public function import(string $filePath): array
    {
        $this->errors       = [];
        $this->successCount = 0;
        $this->failedCount  = 0;
        $this->skippedCount = 0;

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'csv') {
            $rows = $this->readCsv($filePath);
            if (empty($rows)) {
                return $this->getResults();
            }
            $headerRow     = array_map('trim', array_map('strval', $rows[0]));
            $dataRows      = array_slice($rows, 1);
            $dataAssoc     = $this->rowsToAssoc($headerRow, $dataRows, 2);
            $householdsMap = [];
            foreach ($dataAssoc as $rowNumber => $assoc) {
                if ($this->isEmptyRow($assoc)) continue;
                $this->processRow($assoc, $rowNumber, $householdsMap);
            }
            return $this->getResults();
        }

        if ($ext === 'xlsx') {
            $rows = $this->readXlsx($filePath);
            if (empty($rows)) {
                $this->errors[] = ['row' => 0, 'message' => 'Could not read the Excel file. Use a valid .xlsx file with the first sheet containing headers (e.g. Last Name, First Name) and data rows.', 'data' => []];
                return $this->getResults();
            }
            return $this->importXlsx($rows);
        }

        $this->errors[] = ['row' => 0, 'message' => 'Unsupported file type. Use .csv or .xlsx', 'data' => []];
        return $this->getResults();
    }

    // ── XLSX import ────────────────────────────────────────────────────────────

    protected function importXlsx(array $rows): array
    {
        $detected     = $this->detectXlsxHeaderRow($rows);
        $headerRow    = $detected['headers'];
        $dataRows     = $detected['data_rows'];
        $dataStartRow = $detected['data_start_row'];

        if (empty($headerRow)) {
            $this->errors[] = ['row' => 0, 'message' => 'Could not detect header row. Ensure the first row (or first two rows) contains column names such as "Last Name", "First Name", or "Purok".', 'data' => []];
            return $this->getResults();
        }

        $dataAssoc     = $this->rowsToAssoc($headerRow, $dataRows, $dataStartRow);
        $hhRows        = [];
        $nonHhRows     = [];
        $householdsMap = [];

        foreach ($dataAssoc as $rowNumber => $assoc) {
            if ($this->isEmptyRow($assoc)) {
                continue;
            }

            $normalized = $this->normalizeRow($assoc);
            $rel        = trim((string) ($normalized['relationship_to_hh'] ?? ''));
            $systemRel  = $rel !== '' ? PatientImportService::mapRelationshipToHhToSystem($rel) : null;

            if ($systemRel === 'Household-Head') {
                $hhRows[$rowNumber] = $assoc;
            } else {
                $nonHhRows[$rowNumber] = $assoc;
            }
        }

        // Pass 1 — Household Heads first so their IDs exist for members
        foreach ($hhRows as $rowNumber => $assoc) {
            $this->processRow($assoc, $rowNumber, $householdsMap);
        }

        // Pass 2 — For Son, Daughter, Wife, Commonlaw partner, Brother: resolve Household Head
        // (from same import or existing in DB) and set household_head_id
        foreach ($nonHhRows as $rowNumber => $assoc) {
            $normalized = $this->normalizeRow($assoc);
            $headName   = trim((string) ($normalized['household_head_name'] ?? ''));
            $rel        = trim((string) ($normalized['relationship_to_hh'] ?? ''));
            $systemRel  = $rel !== '' ? PatientImportService::mapRelationshipToHhToSystem($rel) : null;

            $shouldLinkToHead = in_array($systemRel, ['Child', 'Spouse', 'Sibling'], true);

            if ($shouldLinkToHead && $headName !== '') {
                $resolvedId = $this->resolveHouseholdHeadId($headName, $householdsMap);
                if ($resolvedId) {
                    $assoc['household_head_id'] = $resolvedId;
                }
            }

            $this->processRow($assoc, $rowNumber, $householdsMap);
        }

        return $this->getResults();
    }

    // ── Header detection ───────────────────────────────────────────────────────

    protected function detectXlsxHeaderRow(array $rows): array
    {
        $row1 = array_values($rows[0] ?? []);
        $row2 = array_values($rows[1] ?? []);

        $row2HasHeaders = false;
        foreach ($row2 as $cell) {
            $c = strtolower(trim(str_replace(["\n", "\r"], ' ', (string) $cell)));
            if (str_contains($c, 'last name') || str_contains($c, 'first name') || $c === 'purok') {
                $row2HasHeaders = true;
                break;
            }
        }

        if ($row2HasHeaders) {
            $maxCol    = max(count($row1), count($row2));
            $headerRow = [];
            for ($i = 0; $i < $maxCol; $i++) {
                $r2          = trim(str_replace(["\n", "\r"], ' ', (string) ($row2[$i] ?? '')));
                $r1          = trim(str_replace(["\n", "\r"], ' ', (string) ($row1[$i] ?? '')));
                $headerRow[] = $r2 !== '' ? $r2 : $r1;
            }
            $dataRows     = array_slice($rows, 2);
            $dataStartRow = 3;
        } else {
            $headerRow    = array_map(fn ($v) => trim(str_replace(["\n", "\r"], ' ', (string) $v)), $row1);
            $dataRows     = array_slice($rows, 1);
            $dataStartRow = 2;
        }

        $firstHeader = trim((string) ($headerRow[0] ?? ''));
        if ($firstHeader === '' || is_numeric($firstHeader)) {
            array_shift($headerRow);
            $dataRows = array_map(fn ($row) => array_slice(array_values($row), 1), $dataRows);
        }

        return [
            'headers'        => array_values($headerRow),
            'data_rows'      => $dataRows,
            'data_start_row' => $dataStartRow,
        ];
    }

    // ── Row helpers ────────────────────────────────────────────────────────────

    protected function rowsToAssoc(array $headerRow, array $dataRows, int $dataStartRow = 2): array
    {
        $out       = [];
        $rowNumber = $dataStartRow;
        foreach ($dataRows as $row) {
            $assoc = [];
            foreach ($headerRow as $j => $key) {
                $assoc[$key] = $row[$j] ?? null;
            }
            $out[$rowNumber] = $assoc;
            $rowNumber++;
        }
        return $out;
    }

    protected function isEmptyRow(array $assoc): bool
    {
        return empty(array_filter($assoc, fn ($v) => $v !== null && trim((string) $v) !== ''));
    }

    // ── Process a single row ───────────────────────────────────────────────────

    protected function processRow(array $rowArray, int $rowNumber, array &$householdsMap): void
    {
        try {
            $normalizedRow = $this->normalizeRow($rowArray);

            // Ensure birth_date is a Y-m-d string
            if (!empty($normalizedRow['birth_date'])) {
                if ($normalizedRow['birth_date'] instanceof \DateTime || $normalizedRow['birth_date'] instanceof \Carbon\Carbon) {
                    $normalizedRow['birth_date'] = $normalizedRow['birth_date']->format('Y-m-d');
                } else {
                    $normalizedRow['birth_date'] = str_replace('/', '-', (string) $normalizedRow['birth_date']);
                    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $normalizedRow['birth_date'], $m)) {
                        $normalizedRow['birth_date'] = $m[1];
                    }
                }
            }

            $reflection     = new \ReflectionClass($this->importService);
            $validateMethod = $reflection->getMethod('validateRow');
            $validateMethod->setAccessible(true);
            $validated = $validateMethod->invoke($this->importService, $normalizedRow, $rowNumber);

            // ── FIX: Preserve household_head_id even if the validator does not include it ──
            if (!empty($normalizedRow['household_head_id'])) {
                $validated['household_head_id'] = $normalizedRow['household_head_id'];
            }

            $createMethod = $reflection->getMethod('createPatient');
            $createMethod->setAccessible(true);
            $patient = $createMethod->invoke($this->importService, $validated);

            // Register HH in map so members can resolve their household_head_id
            if (($validated['relationship_to_head_of_family'] ?? '') === 'Household-Head') {
                $key = $this->normalizeHouseholdHeadKey(
                    ($validated['last_name'] ?? '') . ',' . ($validated['first_name'] ?? '')
                );
                $householdsMap[$key] = $patient->id;
            }

            $this->successCount++;

        } catch (DuplicatePatientException $e) {
            // Duplicate HH still needs to be in the map so its members resolve correctly
            $this->skippedCount++;
            if (!empty($e->existingPatient)) {
                $key = $this->normalizeHouseholdHeadKey(
                    ($e->existingPatient->last_name ?? '') . ',' . ($e->existingPatient->first_name ?? '')
                );
                $householdsMap[$key] = $e->existingPatient->id;
            }
        } catch (\Exception $e) {
            $this->failedCount++;
            $this->errors[] = [
                'row'     => $rowNumber,
                'message' => $e->getMessage(),
                'data'    => $rowArray,
            ];
        }
    }

    // ── Row normalisation ──────────────────────────────────────────────────────

    protected function normalizeRow(array $row): array
    {
        // Build a lowercase+underscore key map for flexible header matching
        $lcMap = [];
        foreach ($row as $key => $value) {
            $lc          = strtolower(trim(str_replace([' ', '-', "\n", "\r", '.'], '_', (string) $key)));
            $lcMap[$lc]  = $key;
        }

        $get = function (array $aliases) use ($row, $lcMap) {
            foreach ($aliases as $alias) {
                $lc = strtolower(str_replace([' ', '-', '.'], '_', $alias));
                if (isset($lcMap[$lc])) {
                    $v = $row[$lcMap[$lc]];
                    if ($v !== null && trim((string) $v) !== '') {
                        return $v;
                    }
                }
            }
            return null;
        };

        $normalized = [
            'last_name'              => $get(['last_name', 'lastname', 'Last Name']),
            'first_name'             => $get(['first_name', 'firstname', 'First Name']),
            'middle_name'            => $get(['middle_name', 'middlename', 'Middle Name']),
            'suffix'                 => $get(['suffix', 'ext', 'ext.', 'Ext.']),
            'purok'                  => $get(['purok', 'Purok']),
            'birth_date'             => $get(['birth_date', 'birthdate', 'date_of_birth', 'Date of Birth']),
            'age'                    => $get(['age', 'Age']),
            'sex'                    => $this->normalizeSex($get(['sex', 'gender', 'Gender'])),
            'civil_status'           => $get(['civil_status', 'civilstatus', 'Civil Status']),
            'contact_number'         => $get(['contact_number', 'contactnumber', 'Contact Number', 'phone']),
            'occupation'             => $get(['occupation', 'Occupation']),
            'blood_pressure'         => $get(['blood_pressure', 'bloodpressure', 'Blood Pressure']),
            'sugar_level'            => $get(['sugar_level', 'sugarlevel']),
            'height'                 => $get(['height', 'Height']),
            'weight'                 => $get(['weight', 'Weight']),
            'place_of_birth'         => $get(['place_of_birth', 'placeofbirth']),
            'educational_attainment' => $get(['educational_attainment', 'educationalattainment']),
            'category'               => $get(['category']),

            // Template columns
            'relationship_to_hh'     => $get(['relationship_to_hh', 'Relationship to HH']),
            'household_head_name'    => $get(['household_head_name', 'household_head', 'Household Head']),
            'birth_order'            => $get(['birth_order', 'Birth Order', 'birth_order_(ika_pila_nga_anak)', 'Birth Order (ika pila nga anak)']),
            'blood_type'             => $get(['blood_type', 'Blood Type']),
            'indigent'               => $get(['indigent', 'Indigent']),
            'pwd'                    => $get(['pwd', 'PWD']),
            'renter'                 => $get(['renter', 'RENTER']),
            'solo_parent'            => $get(['solo_parent', 'SOLO PARENT']),
            'senior_citizen'         => $get(['senior_citizen', 'SEÑIOR CITIZEN', 'SENIOR CITIZEN']),
            'household_no'           => $get(['household_no', 'HOUSEHOLD NO_', 'HOUSEHOLD NO.', 'HOUSEHOLD N']),
            'precinct_no'            => $get(['precinct_no', 'PRECINCT NO_', 'PRECINCT NO.', 'PRECINCT NO']),

            // Pre-resolved by importXlsx pass 2
            'household_head_id'      => $row['household_head_id'] ?? null,
        ];

        // Excel serial date conversion
        if (!empty($normalized['birth_date'])) {
            $normalized['birth_date'] = $this->convertExcelDate($normalized['birth_date']);
        }

        // Contact number: Excel reads 09xxxxxxxxx as float 9123456789.0
        if (!empty($normalized['contact_number']) && is_numeric($normalized['contact_number'])) {
            $cn = (string) (int) $normalized['contact_number'];
            if (!str_starts_with($cn, '0')) {
                $cn = '0' . $cn;
            }
            $normalized['contact_number'] = $cn;
        }

        return array_filter($normalized, fn ($v) => $v !== null);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    protected function normalizeSex(mixed $value): ?string
    {
        if ($value === null) return null;
        $v = strtoupper(trim((string) $value));
        if (in_array($v, ['M', 'MALE']))   return 'male';
        if (in_array($v, ['F', 'FEMALE'])) return 'female';
        return null;
    }

    protected function normalizeHouseholdHeadKey(string $name): string
    {
        return strtoupper(trim(preg_replace('/\s+/', ' ', $name)));
    }

    /**
     * Find household head id by the name in the "Household Head" column: check import map first,
     * then DB by "LastName, FirstName" or by single name (last_name or first_name) in current user's barangay.
     */
    protected function resolveHouseholdHeadId(string $raw, array $householdsMap): ?int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        // Try direct key match first (e.g. "DALIAN,JOHN LOUIE" from same import)
        $key = $this->normalizeHouseholdHeadKey($raw);
        if (isset($householdsMap[$key])) {
            return (int) $householdsMap[$key];
        }

        // Try normalising spaces around the comma
        $normalized = preg_replace('/\s*,\s*/', ',', $key);
        if (isset($householdsMap[$normalized])) {
            return (int) $householdsMap[$normalized];
        }

        $barangayId = \Illuminate\Support\Facades\Auth::user()->barangay_id ?? null;

        // DB lookup: "LastName, FirstName" format
        if (str_contains($raw, ',')) {
            [$lastName, $firstName] = array_map('trim', explode(',', $raw, 2));
            if ($lastName !== '' && $firstName !== '') {
                $query = \App\Models\Patient::whereRaw('UPPER(last_name) = ?', [strtoupper($lastName)])
                    ->whereRaw('UPPER(first_name) = ?', [strtoupper($firstName)]);
                if ($barangayId) {
                    $query->where('barangay_id', $barangayId);
                }
                $id = $query->value('id');
                if ($id !== null) {
                    return (int) $id;
                }
            }
        }

        // Fallback: search by single name (last_name or first_name) in current user's barangay
        $query = \App\Models\Patient::where(function ($q) use ($raw) {
            $q->whereRaw('UPPER(last_name) = ?', [strtoupper($raw)])
                ->orWhereRaw('UPPER(first_name) = ?', [strtoupper($raw)]);
        });
        if ($barangayId) {
            $query->where('barangay_id', $barangayId);
        }
        $id = $query->value('id');

        return $id !== null ? (int) $id : null;
    }

    // ── Date conversion ────────────────────────────────────────────────────────

    protected function convertExcelDate(mixed $value): string
    {
        if ($value instanceof \DateTime || $value instanceof \Carbon\Carbon) {
            return $value->format('Y-m-d');
        }
        if (is_numeric($value)) {
            $date = $this->excelSerialToDate((float) $value);
            return $date ? $date->format('Y-m-d') : (string) $value;
        }
        if (is_string($value)) {
            $value = str_replace('/', '-', trim($value));
            if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $value, $m)) {
                return $m[1];
            }
            // Handle M/D/YYYY or M-D-YYYY (template format e.g. "1/12/1987")
            if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $value, $m)) {
                return sprintf('%04d-%02d-%02d', $m[3], $m[1], $m[2]);
            }
            try {
                return \Carbon\Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception) {
                return $value;
            }
        }
        return (string) $value;
    }

    protected function excelSerialToDate(float $serial): ?\DateTime
    {
        $base = new \DateTime('1899-12-30');
        $days = (int) floor($serial);
        if ($days >= 60) $days--; // Excel 1900 leap-year bug
        $base->add(new \DateInterval('P' . $days . 'D'));
        $frac = $serial - floor($serial);
        if ($frac > 0) {
            $base->add(new \DateInterval('PT' . (int) round($frac * 86400) . 'S'));
        }
        return $base;
    }

    // ── File readers ───────────────────────────────────────────────────────────

    protected function readCsv(string $filePath): array
    {
        $rows   = [];
        $handle = fopen($filePath, 'r');
        if (!$handle) return [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }

    /**
     * Read XLSX using PhpSpreadsheet so the downloaded template (merged cells, shared strings)
     * is parsed correctly and header detection works.
     */
    protected function readXlsx(string $filePath): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet      = $spreadsheet->getActiveSheet();
            $rows       = $sheet->toArray(null, true, false, false);
            // toArray() returns 1-based row indices; normalize to 0-based for header detection
            return array_values($rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Resolve the first worksheet path from workbook relationships (supports any sheet name).
     */
    protected function getFirstSheetPath(\ZipArchive $zip): ?string
    {
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($rels === false) {
            return null;
        }
        $sxe = @simplexml_load_string($rels, 'SimpleXMLElement', LIBXML_NOEMPTYTAG);
        if ($sxe === false) {
            return null;
        }
        foreach ($sxe->Relationship ?? [] as $rel) {
            $type = (string) ($rel['Type'] ?? '');
            if (str_contains($type, 'worksheet')) {
                $target = (string) ($rel['Target'] ?? '');
                if ($target === '') {
                    return null;
                }
                return 'xl/' . ltrim(str_replace('\\', '/', $target), '/');
            }
        }
        return null;
    }

    private const XLSX_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    protected function readXlsxSharedStrings(\ZipArchive $zip): ?array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) return [];
        $sxe = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOEMPTYTAG);
        if ($sxe === false) return [];

        $si   = $sxe->children(self::XLSX_NS)->si ?? $sxe->si;
        $list = [];
        foreach ($si as $item) {
            $text   = '';
            $itemNs = $item->children(self::XLSX_NS);
            if (isset($item->t)) {
                $text = (string) $item->t;
            } elseif (isset($itemNs->r)) {
                foreach ($itemNs->r as $r) $text .= (string) ($r->t ?? '');
            } else {
                foreach ($item->r ?? [] as $r) $text .= (string) ($r->t ?? '');
            }
            $list[] = $text;
        }
        return $list;
    }

    protected function getInlineCellText(\SimpleXMLElement $c): string
    {
        $cellNs = $c->children(self::XLSX_NS);
        $is     = $cellNs->is ?? $c->is ?? null;
        if ($is === null) {
            return '';
        }
        $isNs = $is->children(self::XLSX_NS);
        if (isset($is->t)) {
            return (string) $is->t;
        }
        if (isset($isNs->r)) {
            $text = '';
            foreach ($isNs->r as $r) {
                $text .= (string) ($r->t ?? '');
            }
            return $text;
        }
        $text = '';
        foreach ($is->r ?? [] as $r) {
            $text .= (string) ($r->t ?? '');
        }
        return $text;
    }

    protected function parseSheetXml(string $sheetXml, array $sharedStrings): array
    {
        $sxe = @simplexml_load_string($sheetXml, 'SimpleXMLElement', LIBXML_NOEMPTYTAG);
        if ($sxe === false) return [];

        $sheetData   = $sxe->children(self::XLSX_NS)->sheetData ?? $sxe->sheetData;
        $rowElements = $sheetData->children(self::XLSX_NS)->row ?? $sheetData->row ?? [];
        if (empty($rowElements)) return [];

        $rows = [];
        foreach ($rowElements as $row) {
            $rowNum       = (int) ($row['r'] ?? count($rows) + 1);
            $cells        = [];
            $cellElements = $row->children(self::XLSX_NS)->c ?? $row->c ?? [];
            foreach ($cellElements as $c) {
                $colIndex = $this->columnLettersToIndex((string) $c['r']);
                $cellNs   = $c->children(self::XLSX_NS);
                $t        = (string) ($c['t'] ?? '');
                $value    = null;
                if ($t === 'is') {
                    $value = $this->getInlineCellText($c);
                } else {
                    $vEl = $cellNs->v ?? $c->v;
                    if ($vEl !== null && (string) $vEl !== '') {
                        $v = (string) $vEl;
                        $value = match (true) {
                            $t === 's' => $sharedStrings[(int) $v] ?? '',
                            $t === 'd' => $v,
                            default    => is_numeric($v) ? (float) $v : $v,
                        };
                    }
                }
                $cells[$colIndex] = $value;
            }
            $rows[$rowNum] = $cells;
        }

        if (empty($rows)) return [];

        $minRow = min(array_keys($rows));
        $maxRow = max(array_keys($rows));
        $maxCol = max(array_map(fn ($cells) => empty($cells) ? 0 : max(array_keys($cells)), $rows));

        $out = [];
        for ($r = $minRow; $r <= $maxRow; $r++) {
            $line = [];
            for ($c = 0; $c <= $maxCol; $c++) {
                $line[] = $rows[$r][$c] ?? null;
            }
            $out[] = $line;
        }
        return $out;
    }

    protected function columnLettersToIndex(string $cellRef): int
    {
        $letters = preg_replace('/[0-9]+/', '', $cellRef);
        $col     = 0;
        for ($i = 0; $i < strlen($letters); $i++) {
            $col = $col * 26 + (ord($letters[$i]) - ord('A') + 1);
        }
        return $col > 0 ? $col - 1 : 0;
    }

    // ── Results ────────────────────────────────────────────────────────────────

    public function getResults(): array
    {
        return [
            'success' => $this->successCount,
            'failed'  => $this->failedCount,
            'skipped' => $this->skippedCount,
            'errors'  => $this->errors,
        ];
    }
}