<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;

class ConsultationsExport implements WithEvents, WithColumnWidths
{
    protected $consultations;

    protected $title;

    protected $barangay;

    protected string $preparedByLabel;

    public function __construct($consultations, string $title, $barangay, string $preparedByLabel = '')
    {
        $this->consultations = $consultations;
        $this->title = $title;
        $this->barangay = $barangay;
        $this->preparedByLabel = $preparedByLabel;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25, 'B' => 15, 'C' => 8, 'D' => 18, 'E' => 25, 'F' => 15, 'G' => 12,
            'H' => 18, 'I' => 10, 'J' => 10, 'K' => 10, 'L' => 10, 'M' => 30, 'N' => 18,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = 'N';
                $barangayHeaderText = $this->barangay
                    ? 'BARANGAY ' . strtoupper($this->barangay->name)
                    : 'Barangays of ' . ucfirst(strtolower(config('app.municipality', 'CARMEN')));
                $barangayName = $this->barangay?->name ?? 'All Barangays';
                $dateTime = now()->format('F d, Y h:i A');
                $totalCount = count($this->consultations);
                $province = strtoupper(config('app.province', 'DAVAO DEL NORTE'));
                $municipality = strtoupper(config('app.municipality', 'CARMEN'));

                $logoPath = config('app.export_logo_path');
                if (!$logoPath || !file_exists($logoPath)) {
                    $logoPath = public_path('comm-health-icon.png');
                }
                if (!file_exists($logoPath)) {
                    $logoPath = public_path('images/comm-health-icon.png');
                }
                if (!file_exists($logoPath)) {
                    $logoPath = public_path('images/comm-health-icon.jpg');
                }
                if ($logoPath && file_exists($logoPath)) {
                    $drawing = new Drawing();
                    $drawing->setPath($logoPath);
                    $drawing->setCoordinates('A1');
                    $drawing->setResizeProportional(false);
                    $drawing->setHeight(175);
                    $drawing->setWidth(198);
                    $drawing->setRotation(0);
                    $drawing->setEditAs(BaseDrawing::EDIT_AS_ONECELL);
                    $drawing->setWorksheet($sheet);
                }

                $headerLines = [
                    1 => 'REPUBLIC OF THE PHILIPPINES',
                    2 => 'PROVINCE OF ' . $province,
                    3 => 'MUNICIPAL HEALTH OFFICE',
                    4 => 'MUNICIPALITY OF ' . $municipality,
                    5 => $barangayHeaderText,
                    6 => $this->title,
                ];
                $headerSizes = [1 => 16, 2 => 16, 3 => 24, 4 => 18, 5 => 16, 6 => 22];
                foreach ($headerLines as $row => $text) {
                    $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
                    $sheet->setCellValue("A{$row}", $row === 6 ? $text : strtoupper($text));
                    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setName('Arial')->setSize($headerSizes[$row]);
                }

                $sheet->mergeCells("A8:{$lastCol}8");
                $sheet->setCellValue('A8', 'As of: ' . $dateTime);
                $sheet->getStyle('A8')->getFont()->setItalic(true);

                $cols = [
                    'A' => 'Full Name', 'B' => 'Birthdate', 'C' => 'Age', 'D' => 'Barangay',
                    'E' => 'Category', 'F' => 'Blood Pressure', 'G' => 'Sugar Level', 'H' => 'Contact Number',
                    'I' => 'Gender', 'J' => 'Height', 'K' => 'Weight', 'L' => 'BMI',
                    'M' => 'Maintenance', 'N' => 'Consultation Date',
                ];
                foreach ($cols as $col => $label) {
                    $sheet->setCellValue("{$col}9", $label);
                }
                $sheet->getStyle('A9:N9')->applyFromArray([
                    'font' => ['bold' => true, 'name' => 'Arial'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $startRow = 10;
                $sheet->getStyle('H' . $startRow . ':H' . ($startRow + $totalCount))
                    ->getNumberFormat()
                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

                foreach ($this->consultations as $i => $consult) {
                    $patient = $consult->patient;
                    $row = $startRow + $i;
                    $sheet->setCellValue("A{$row}", trim($patient->first_name . ' ' . ($patient->middle_name ?? '') . ' ' . $patient->last_name));
                    $sheet->setCellValue("B{$row}", $patient->birth_date ? $patient->birth_date->format('M d, Y') : 'N/A');
                    $sheet->setCellValue("C{$row}", $patient->age ?? 'N/A');
                    $sheet->setCellValue("D{$row}", $patient->barangay->name ?? 'N/A');
                    $sheet->setCellValue("E{$row}", $patient->category->name ?? 'N/A');
                    $sheet->setCellValue("F{$row}", $patient->blood_pressure ?? 'N/A');
                    $sheet->setCellValue("G{$row}", $patient->sugar_level ?? 'N/A');
                    $contact = $patient->contact_number ?? 'N/A';
                    if ($contact !== 'N/A' && is_string($contact)) {
                        $contact = preg_replace('/\s+/', '', $contact);
                        if (preg_match('/^0\d{9,10}$/', $contact)) {
                            $contact = '+63' . substr($contact, 1);
                        } elseif (preg_match('/^9\d{9}$/', $contact)) {
                            $contact = '+63' . $contact;
                        } elseif (!str_starts_with($contact, '+')) {
                            $contact = '+63' . ltrim($contact, '63');
                        }
                    }
                    $sheet->setCellValueExplicit(
                        "H{$row}",
                        (string) $contact,
                        DataType::TYPE_STRING
                    );
                    $sheet->setCellValue("I{$row}", $patient->sex ?? 'N/A');
                    $sheet->setCellValue("J{$row}", $patient->height ?? 'N/A');
                    $sheet->setCellValue("K{$row}", $patient->weight ?? 'N/A');
                    $sheet->setCellValue("L{$row}", $patient->bmi ?? 'N/A');
                    $sheet->setCellValue("M{$row}", is_array($patient->medication_maintenance) ? implode(', ', $patient->medication_maintenance) : ($patient->medication_maintenance ?? 'N/A'));
                    $sheet->setCellValue("N{$row}", $consult->date ? $consult->date->format('M d, Y') : 'N/A');
                    $sheet->getStyle("A{$row}:N{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                }

                $totalRow = $startRow + $totalCount + 1;
                $sheet->mergeCells("A{$totalRow}:M{$totalRow}");
                $sheet->setCellValue("A{$totalRow}", 'TOTAL: ' . $totalCount . ' records');
                $sheet->getStyle("A{$totalRow}")->getFont()->setBold(true);

                $footerRow = $totalRow + 3;
                $sheet->setCellValue("A{$footerRow}", 'PREPARED BY:');
                $sheet->setCellValue("J{$footerRow}", 'NOTED BY:');
                $sheet->getStyle("A{$footerRow}")->getFont()->setBold(true);
                $sheet->getStyle("J{$footerRow}")->getFont()->setBold(true);
                $signatureRow = $footerRow + 3;
                $sheet->mergeCells("A{$signatureRow}:B{$signatureRow}");
                $sheet->setCellValue("A{$signatureRow}", $this->preparedByLabel ?: ('BHW - ' . $barangayName));
                $sheet->setCellValue("J{$signatureRow}", 'Municipal Health Office - Admin');
                $sheet->getStyle("A{$signatureRow}")->getFont()->setBold(true);
                $sheet->getStyle("J{$signatureRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$signatureRow}:B{$signatureRow}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("J{$signatureRow}:M{$signatureRow}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
}
