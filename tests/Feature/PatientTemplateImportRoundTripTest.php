<?php

namespace Tests\Feature;

use App\Exports\PatientTemplateExport;
use App\Imports\PatientsImport;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\Purok;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class PatientTemplateImportRoundTripTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Import a file with the same column structure as the downloadable Excel template.
     * Uses CSV with template headers and sample data to verify the import accepts the template format.
     */
    public function test_template_format_can_be_imported_without_errors(): void
    {
        $barangay = Barangay::create(['name' => 'MAGSAYSAY', 'is_active' => true]);
        Purok::create(['name' => 'PUROK 1A', 'barangay_id' => $barangay->id, 'is_active' => true]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'bhw',
            'barangay_id' => $barangay->id,
        ]);

        Category::create([
            'name' => 'Adult',
            'age_min' => 18,
            'age_max' => 120,
            'is_child' => false,
            'is_maternal' => false,
        ]);
        Category::create([
            'name' => 'Child',
            'age_min' => 0,
            'age_max' => 17,
            'is_child' => true,
            'is_maternal' => false,
        ]);

        $this->actingAs($user);

        // CSV with headers matching the Excel template (row 2 sub-headers + row 1 column headers for H–W)
        $headers = [
            'Last Name', 'First Name', 'Middle Name', 'Ext.', 'Purok', 'Brgy.',
            'Relationship to HH', 'Household Head', 'Date of Birth', 'Birth Order (ika pila nga anak)', 'Age', 'Gender',
            'Contact Number', 'Occupation', 'Blood Type', 'Indigent', 'PWD', 'RENTER', 'SOLO PARENT', 'SEÑIOR CITIZEN',
            'HOUSEHOLD NO.', 'PRECINCT NO.',
        ];
        $row1 = [
            'DALIAN', 'JOHN LOUIE', 'SORROSA', '', 'PUROK 1A', 'MAGSAYSAY', 'HH', 'DALIAN, JOHN LOUIE',
            '1987-01-12', '', '38', 'M', '9295379931', 'N/A', '', 'Y', 'N', 'N', 'N', 'N', '', '0082B',
        ];
        $csv = $this->buildCsv([$headers, $row1]);
        $path = sys_get_temp_dir() . '/patient_template_roundtrip_' . uniqid() . '.csv';
        file_put_contents($path, $csv);

        try {
            $import = new PatientsImport();
            $result = $import->import($path);

            $this->assertEmpty($result['errors'], 'Import should have no errors. Got: ' . json_encode($result['errors'], JSON_PRETTY_PRINT));
            $this->assertGreaterThanOrEqual(1, $result['success'] + $result['skipped'], 'At least one row should be processed.');
        } finally {
            @unlink($path);
        }
    }

    /**
     * Export the downloadable Excel template and re-import it; header row must be detected so data can be saved.
     */
    public function test_downloaded_excel_template_can_be_imported(): void
    {
        $barangay = Barangay::create(['name' => 'MAGSAYSAY', 'is_active' => true]);
        Purok::create(['name' => 'PUROK 1A', 'barangay_id' => $barangay->id, 'is_active' => true]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test-excel@example.com',
            'password' => bcrypt('password'),
            'role' => 'bhw',
            'barangay_id' => $barangay->id,
        ]);

        Category::create([
            'name' => 'Adult',
            'age_min' => 18,
            'age_max' => 120,
            'is_child' => false,
            'is_maternal' => false,
        ]);
        Category::create([
            'name' => 'Child',
            'age_min' => 0,
            'age_max' => 17,
            'is_child' => true,
            'is_maternal' => false,
        ]);

        $this->actingAs($user);

        $path = 'template_xlsx_roundtrip_' . uniqid() . '.xlsx';
        Excel::store(new PatientTemplateExport(), $path, 'local');
        $fullPath = Storage::disk('local')->path($path);

        try {
            $import = new PatientsImport();
            $result = $import->import($fullPath);

            $this->assertEmpty($result['errors'], 'Downloaded template XLSX should import without "file format not recognised". Got: ' . json_encode($result['errors'], JSON_PRETTY_PRINT));
            $totalProcessed = $result['success'] + $result['skipped'] + $result['failed'];
            $this->assertGreaterThanOrEqual(8, $totalProcessed, 'All 8 sample rows in the template should be processed.');
        } finally {
            Storage::disk('local')->delete($path);
        }
    }

    private function buildCsv(array $rows): string
    {
        $stream = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        return $csv;
    }
}
