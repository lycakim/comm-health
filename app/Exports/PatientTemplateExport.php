<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PatientTemplateExport implements FromArray, WithStyles, WithColumnWidths, WithEvents
{
    private const SAMPLE_ROWS = [
        ['DELA CRUZ', 'JUAN',       'SANTOS',    '',  'PUROK 1A', 'SAMPLE BRGY', 'HH',               'DELA CRUZ, JUAN',    '1/1/1985',   '',     40, 'M', '9000000001', 'N/A',          '',  'Y', 'N', 'N', 'N', 'N', '',       '0001A'],
        ['DELA CRUZ', 'MARIA',      'REYES',     '',  'PUROK 1A', 'SAMPLE BRGY', 'WIFE',             'DELA CRUZ, JUAN',    '3/15/1987',  '',     38, 'F', '',           'HOUSEWIFE',    '',  'Y', 'N', 'N', 'N', 'N', '',       '0001A'],
        ['DELA CRUZ', 'JOSE',       'SANTOS',    '',  'PUROK 1A', 'SAMPLE BRGY', 'SON',              'DELA CRUZ, JUAN',    '6/20/2010',  '1st',  15, 'M', '',           'STUDENT',      '',  'Y', 'N', 'N', 'N', 'N', '',       ''],
        ['DELA CRUZ', 'ANA',        'SANTOS',    '',  'PUROK 1A', 'SAMPLE BRGY', 'DAUGHTER',         'DELA CRUZ, JUAN',    '9/5/2013',   '2nd',  12, 'F', '',           'STUDENT',      '',  'Y', 'N', 'N', 'N', 'N', '',       ''],
        ['GARCIA',   'PEDRO',       'LOPES',     '',  'PUROK 2B', 'SAMPLE BRGY', 'HH',               'GARCIA, PEDRO',      '4/10/1980',  '',     45, 'M', '9000000002', 'FARMER',       'O', 'Y', 'N', 'N', 'N', 'N', '',       '0002B'],
        ['GARCIA',   'ROSA',        'MENDOZA',   '',  'PUROK 2B', 'SAMPLE BRGY', 'WIFE',             'GARCIA, PEDRO',      '7/22/1983',  '',     42, 'F', '',           'N/A',          'A', 'Y', 'N', 'Y', 'N', 'N', '',       '0002B'],
        ['GARCIA',   'CARLO',       'LOPES',     '',  'PUROK 2B', 'SAMPLE BRGY', 'SON',              'GARCIA, PEDRO',      '2/18/2006',  '1st',  19, 'M', '',           'STUDENT',      '',  'Y', 'N', 'N', 'N', 'N', '',       ''],
        ['GARCIA',   'LUCIA',       'LOPES',     '',  'PUROK 2B', 'SAMPLE BRGY', 'DAUGHTER',         'GARCIA, PEDRO',      '11/30/2009', '2nd',  16, 'F', '',           'STUDENT',      '',  'Y', 'N', 'N', 'N', 'N', '',       ''],
    ];

    public function array(): array
    {
        return collect(self::SAMPLE_ROWS)
            ->map(fn ($row, $i) => array_merge([$i + 1], $row))
            ->values()
            ->toArray();
    }

    public function columnWidths(): array
    {
        return [
            'A' => 4,   'B' => 14,  'C' => 14,  'D' => 14,
            'E' => 5,   'F' => 12,  'G' => 14,  'H' => 20,
            'I' => 22,  'J' => 12,  'K' => 10,  'L' => 5,
            'M' => 7,   'N' => 15,  'O' => 16,  'P' => 8,
            'Q' => 8,   'R' => 7,   'S' => 8,   'T' => 11,
            'U' => 11,  'V' => 14,  'W' => 12,
        ];
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        // Plain style for all data rows — no background color
        foreach (self::SAMPLE_ROWS as $i => $_) {
            $excelRow = $i + 3;
            $sheet->getStyle("A{$excelRow}:W{$excelRow}")->applyFromArray([
                'font'      => ['name' => 'Arial', 'size' => 9],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'AAAAAA']]],
            ]);
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->insertNewRowBefore(1, 2);

                // Col A rows 1–2: empty merged cell
                $sheet->mergeCells('A1:A2');
                $this->applyHeaderStyle($sheet, 'A1:A2');

                // B1:E1 — Name of Head of Household
                $sheet->mergeCells('B1:E1');
                $sheet->getCell('B1')->setValue('Name of Head of Household');
                $this->applyHeaderStyle($sheet, 'B1:E1');

                // F1:G1 — Address (Prk, Brgy.)
                $sheet->mergeCells('F1:G1');
                $sheet->getCell('F1')->setValue('Address (Prk, Brgy.)');
                $this->applyHeaderStyle($sheet, 'F1:G1');

                // H–W: single columns spanning rows 1–2
                $spanHeaders = [
                    'H' => 'Relationship to HH',
                    'I' => 'Household Head',
                    'J' => 'Date of Birth',
                    'K' => "Birth Order\n(ika pila nga anak)",
                    'L' => 'Age',
                    'M' => 'Gender',
                    'N' => 'Contact Number',
                    'O' => 'Occupation',
                    'P' => 'Blood Type',
                    'Q' => 'Indigent',
                    'R' => 'PWD',
                    'S' => 'RENTER',
                    'T' => 'SOLO PARENT',
                    'U' => 'SEÑIOR CITIZEN',
                    'V' => 'HOUSEHOLD NO.',
                    'W' => 'PRECINCT NO.',
                ];
                foreach ($spanHeaders as $col => $label) {
                    $sheet->mergeCells("{$col}1:{$col}2");
                    $sheet->getCell("{$col}1")->setValue($label);
                    $this->applyHeaderStyle($sheet, "{$col}1:{$col}2", wrapText: true);
                }

                // Row 2 sub-headers
                $subHeaders = [
                    'B2' => 'Last Name',
                    'C2' => 'First Name',
                    'D2' => 'Middle Name',
                    'E2' => 'Ext.',
                    'F2' => 'Purok',
                    'G2' => 'Brgy.',
                ];
                foreach ($subHeaders as $cell => $label) {
                    $sheet->getCell($cell)->setValue($label);
                    $this->applyHeaderStyle($sheet, $cell);
                }

                $sheet->getRowDimension(1)->setRowHeight(36);
                $sheet->getRowDimension(2)->setRowHeight(20);
                for ($r = 3; $r <= 2 + count(self::SAMPLE_ROWS); $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(16);
                }

                $sheet->freezePane('B3');
            },
        ];
    }

    private function applyHeaderStyle(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        string $range,
        bool $wrapText = false
    ): void {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'bold' => true,
                'size' => 9,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => $wrapText,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'AAAAAA'],
                ],
            ],
        ]);
    }

    public static function download(string $filename = 'household_template.xlsx'): BinaryFileResponse
    {
        return Excel::download(new self(), $filename);
    }
}