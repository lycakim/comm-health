<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;

class GenericReportExport implements WithEvents, WithColumnWidths
{
    protected array $headers;

    protected array $rows;

    protected string $title;

    protected $barangay;

    protected string $preparedByLabel;

    public function __construct(array $headers, array $rows, string $title, $barangay, string $preparedByLabel = '')
    {
        $this->headers = $headers;
        $this->rows = $rows;
        $this->title = $title;
        $this->barangay = $barangay;
        $this->preparedByLabel = $preparedByLabel;
    }

    public function columnWidths(): array
    {
        $widths = [];
        $count = count($this->headers);
        for ($i = 1; $i <= $count; $i++) {
            $widths[Coordinate::stringFromColumnIndex($i)] = 18;
        }
        return $widths;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $colCount = count($this->headers);
                $lastCol = Coordinate::stringFromColumnIndex($colCount);
                $barangayHeaderText = $this->barangay
                    ? 'BARANGAY ' . strtoupper($this->barangay->name)
                    : 'Barangays of ' . ucfirst(strtolower(config('app.municipality', 'CARMEN')));
                $barangayName = $this->barangay?->name ?? 'All Barangays';
                $dateTime = now()->format('F d, Y h:i A');
                $totalCount = count($this->rows);
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
                $headerEndCol = $lastCol; // B through lastCol for header text (logo in A)
                foreach ($headerLines as $row => $text) {
                    $sheet->mergeCells("A{$row}:{$headerEndCol}{$row}");
                    $sheet->setCellValue("A{$row}", $row === 6 ? $text : strtoupper($text));
                    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setName('Arial')->setSize($headerSizes[$row]);
                }

                $sheet->mergeCells("A8:{$lastCol}8");
                $sheet->setCellValue('A8', 'As of: ' . $dateTime);
                $sheet->getStyle('A8')->getFont()->setItalic(true);

                foreach ($this->headers as $i => $label) {
                    $col = Coordinate::stringFromColumnIndex($i + 1);
                    $sheet->setCellValue("{$col}9", $label);
                }

                $sheet->getStyle("A9:{$lastCol}9")->applyFromArray([
                    'font' => ['bold' => true, 'name' => 'Arial'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $startRow = 10;
                foreach ($this->rows as $i => $rowData) {
                    $row = $startRow + $i;
                    foreach ($rowData as $j => $value) {
                        $col = Coordinate::stringFromColumnIndex($j + 1);
                        $sheet->setCellValue("{$col}{$row}", $value ?? '');
                    }
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                }

                $totalRow = $startRow + $totalCount + 1;
                $mergeEndCol = Coordinate::stringFromColumnIndex(max(1, $colCount - 1));
                $sheet->mergeCells("A{$totalRow}:{$mergeEndCol}{$totalRow}");
                $sheet->setCellValue("A{$totalRow}", 'TOTAL: ' . $totalCount . ' records');
                $sheet->getStyle("A{$totalRow}")->getFont()->setBold(true);

                $footerRow = $totalRow + 3;
                $preparedEndCol = Coordinate::stringFromColumnIndex(2); // A:B
                $notedStartCol = Coordinate::stringFromColumnIndex(max(3, $colCount - 3)); // last 4 columns
                $sheet->setCellValue("A{$footerRow}", 'PREPARED BY:');
                $sheet->setCellValue("{$notedStartCol}{$footerRow}", 'NOTED BY:');
                $sheet->getStyle("A{$footerRow}")->getFont()->setBold(true);
                $sheet->getStyle("{$notedStartCol}{$footerRow}")->getFont()->setBold(true);
                $signatureRow = $footerRow + 3;
                $sheet->setCellValue("A{$signatureRow}", $this->preparedByLabel ?: ('BHW - ' . $barangayName));
                $sheet->setCellValue("{$notedStartCol}{$signatureRow}", 'Municipal Health Office - Admin');
                $sheet->getStyle("A{$signatureRow}")->getFont()->setBold(true);
                $sheet->getStyle("{$notedStartCol}{$signatureRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$signatureRow}:{$preparedEndCol}{$signatureRow}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("{$notedStartCol}{$signatureRow}:{$lastCol}{$signatureRow}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
}
