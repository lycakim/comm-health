<?php

namespace App\Exports;

use App\Services\PatientImportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientTemplateExport
{
    /**
     * Build the spreadsheet (headers, sample row, column widths).
     */
    public function spreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = PatientImportService::getTemplateHeaders();
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Sample data row
        $sampleRow = [
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
        ];
        $col = 'A';
        foreach ($sampleRow as $value) {
            $sheet->setCellValue($col . '2', $value);
            $col++;
        }

        // Column widths
        $widths = [
            'A' => 15, 'B' => 15, 'C' => 15, 'D' => 10, 'E' => 15,
            'F' => 10, 'G' => 15, 'H' => 15, 'I' => 15, 'J' => 20,
            'K' => 20, 'L' => 15, 'M' => 15, 'N' => 10, 'O' => 10,
            'P' => 25, 'Q' => 20,
        ];
        foreach ($widths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        return $spreadsheet;
    }

    /**
     * Return a streamed download response for the template.
     */
    public static function download(string $filename): StreamedResponse
    {
        $export = new self();
        $spreadsheet = $export->spreadsheet();
        $writer = new Xlsx($spreadsheet);

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}
