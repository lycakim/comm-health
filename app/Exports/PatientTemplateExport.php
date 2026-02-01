<?php

namespace App\Exports;

use App\Services\PatientImportService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PatientTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    /**
     * @return array
     */
    public function array(): array
    {
        // Return sample data row (barangay excluded - uses current user's barangay)
        return [
            [
                'Juan',
                'Manuel',
                'Dela Cruz',
                'Jr.',
                '1990-01-15',
                'male',
                'Single',
                '09123456789',
                'Sample Purok',
                '',
                'Farmer',
                '120/80',
                '90',
                '170',
                '70',
                'Manila',
                'College Graduate',
            ],
        ];
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return PatientImportService::getTemplateHeaders();
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        // Return empty array - no styling (normal template)
        return [];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15, // first_name
            'B' => 15, // middle_name
            'C' => 15, // last_name
            'D' => 10, // suffix
            'E' => 15, // birth_date
            'F' => 10, // sex
            'G' => 15, // civil_status
            'H' => 15, // contact_number
            'I' => 15, // purok
            'J' => 20, // category
            'K' => 20, // occupation
            'L' => 15, // blood_pressure
            'M' => 15, // sugar_level
            'N' => 10, // height
            'O' => 10, // weight
            'P' => 25, // place_of_birth
            'Q' => 20, // educational_attainment
        ];
    }
}

