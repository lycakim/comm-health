<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;

class ReferralsExport implements WithEvents, WithColumnWidths
{
    protected $referrals;

    protected $title;

    protected $barangay;

    protected string $preparedByLabel;

    public function __construct($referrals, string $title, $barangay, string $preparedByLabel = '')
    {
        $this->referrals = $referrals;
        $this->title = $title;
        $this->barangay = $barangay;
        $this->preparedByLabel = $preparedByLabel;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18, 'B' => 25, 'C' => 8, 'D' => 10, 'E' => 15, 'F' => 18,
            'G' => 12, 'H' => 12, 'I' => 25, 'J' => 20, 'K' => 20,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = 'K';
                $barangayHeaderText = $this->barangay
                    ? 'BARANGAY ' . strtoupper($this->barangay->name)
                    : 'Barangays of ' . ucfirst(strtolower(config('app.municipality', 'CARMEN')));
                $barangayName = $this->barangay?->name ?? 'All Barangays';
                $dateTime = now()->format('F d, Y h:i A');
                $totalCount = count($this->referrals);
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
                    'A' => 'Reference ID', 'B' => 'Patient Name', 'C' => 'Age', 'D' => 'Gender',
                    'E' => 'Purok', 'F' => 'Barangay', 'G' => 'Urgency', 'H' => 'Status',
                    'I' => 'Referred To', 'J' => 'Referred By', 'K' => 'Date Referred',
                ];
                foreach ($cols as $col => $label) {
                    $sheet->setCellValue("{$col}9", $label);
                }
                $sheet->getStyle('A9:K9')->applyFromArray([
                    'font' => ['bold' => true, 'name' => 'Arial'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $startRow = 10;
                foreach ($this->referrals as $i => $referral) {
                    $patient = $referral->patient ?? $referral->consultation?->patient;
                    $row = $startRow + $i;
                    $sheet->setCellValue("A{$row}", $referral->id);
                    $sheet->setCellValue("B{$row}", $patient ? ($patient->first_name . ' ' . $patient->last_name) : 'N/A');
                    $sheet->setCellValue("C{$row}", $patient?->age ?? 'N/A');
                    $sheet->setCellValue("D{$row}", $patient?->sex ?? 'N/A');
                    $sheet->setCellValue("E{$row}", $patient?->purok?->name ?? 'N/A');
                    $sheet->setCellValue("F{$row}", $patient?->barangay?->name ?? 'N/A');
                    $sheet->setCellValue("G{$row}", $referral->urgency ?? 'N/A');
                    $sheet->setCellValue("H{$row}", $referral->status ?? 'N/A');
                    $sheet->setCellValue("I{$row}", $referral->referred_to ?? 'N/A');
                    $sheet->setCellValue("J{$row}", $referral->user?->name ?? 'N/A');
                    $sheet->setCellValue("K{$row}", $referral->date_referred ? $referral->date_referred->format('Y-m-d H:i:s') : ($referral->created_at->format('Y-m-d H:i:s')));
                    $sheet->getStyle("A{$row}:K{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                }

                $totalRow = $startRow + $totalCount + 1;
                $sheet->mergeCells("A{$totalRow}:J{$totalRow}");
                $sheet->setCellValue("A{$totalRow}", 'TOTAL: ' . $totalCount . ' records');
                $sheet->getStyle("A{$totalRow}")->getFont()->setBold(true);

                $footerRow = $totalRow + 3;
                $sheet->setCellValue("A{$footerRow}", 'PREPARED BY:');
                $sheet->setCellValue("H{$footerRow}", 'NOTED BY:');
                $sheet->getStyle("A{$footerRow}")->getFont()->setBold(true);
                $sheet->getStyle("H{$footerRow}")->getFont()->setBold(true);
                $signatureRow = $footerRow + 3;
                $sheet->mergeCells("A{$signatureRow}:B{$signatureRow}");
                $sheet->setCellValue("A{$signatureRow}", $this->preparedByLabel ?: ('BHW - ' . $barangayName));
                $sheet->setCellValue("H{$signatureRow}", 'Municipal Health Office - Admin');
                $sheet->getStyle("A{$signatureRow}")->getFont()->setBold(true);
                $sheet->getStyle("H{$signatureRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$signatureRow}:B{$signatureRow}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("H{$signatureRow}:K{$signatureRow}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
}
