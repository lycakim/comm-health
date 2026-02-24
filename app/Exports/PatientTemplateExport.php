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
        ['DALIAN',  'JOHN LOUIE',  'SORROSA',   '',  'PUROK 1A', 'MAGSAYSAY', 'HH',               'DALIAN, JOHN LOUIE', '1/12/1987',  '',     38, 'M', '9295379931', 'N/A',          '',  'Y', 'N', 'N', 'N', 'N', '',       '0082B'],
        ['DALIAN',  'AEGEUS AART', 'AQUINO',    '',  'PUROK 1A', 'MAGSAYSAY', 'SON',              'DALIAN, JOHN LOUIE', '3/11/2019',  '1st',   5, 'M', '',           'STUDENT',      '',  'Y', 'N', 'N', 'N', 'N', '',       ''],
        ['AQUINO',  'MARICEL ANN', 'CASTRO',    '',  'PUROK 1A', 'MAGSAYSAY', 'COMMONLAW PARTNER','DALIAN, JOHN LOUIE', '12/2/1987',  '',     38, 'F', '',           'ONLINE SELLER','',  'Y', 'N', 'N', 'N', 'N', '',       ''],
        ['DALIAN',  'TRENCE',      'SORROSA',   '',  'PUROK 1A', 'MAGSAYSAY', 'BROTHER',          'DALIAN, JOHN LOUIE', '5/24/1988',  '',     37, 'M', '9053649316', 'NURSE',        '',  'Y', 'N', 'N', 'N', 'N', '',       '0082B'],
        ['PATES',   'ALEXANDER',   'FAJARDO',   '',  'PUROK 1A', 'MAGSAYSAY', 'HH',               'PATES,ALEXANDER',    '11/7/1987',  '',     36, 'M', '965266659',  'MP',           'O', 'Y', 'N', 'N', 'N', 'N', '',       '0083A'],
        ['PATES',   'MICHELLE',    'SABELLANO', '',  'PUROK 1A', 'MAGSAYSAY', 'WIFE',             'PATES,ALEXANDER',    '12/8/1977',  '',     46, 'F', '',           'CS',           'O', 'Y', 'N', 'Y', 'N', 'N', '',       '0083A'],
        ['PATES',   'CLEXY SLH',   'SABELLANO', '',  'PUROK 1A', 'MAGSAYSAY', 'DAUGHTER',         'PATES,ALEXANDER',    '8/16/2008',  '1st',  15, 'F', '',           'STUDENT',      '',  'Y', 'N', 'N', 'N', 'N', '',       ''],
        ['PATES',   'SWIFY YMS',   'SABELLANO', '',  'PUROK 1A', 'MAGSAYSAY', 'DAUGHTER',         'PATES,ALEXANDER',    '10/26/2011', '2nd',  12, 'F', '',           'STUDENT',      '',  'Y', 'N', 'N', 'N', 'N', '',       ''],
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