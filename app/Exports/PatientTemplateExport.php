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
        // Return sample data row
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
                'Sample Barangay',
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
        return [
            // Style the header row
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
            // Style the sample row
            2 => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E7E6E6'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
        ];
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
            'I' => 20, // barangay
            'J' => 15, // purok
            'K' => 20, // category
            'L' => 20, // occupation
            'M' => 15, // blood_pressure
            'N' => 15, // sugar_level
            'O' => 10, // height
            'P' => 10, // weight
            'Q' => 25, // place_of_birth
            'R' => 20, // educational_attainment
        ];
    }
}

