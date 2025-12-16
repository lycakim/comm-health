<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use App\Services\SeniorCitizenChartService;

class SeniorCitizenChart extends ChartWidget
{
    protected static ?string $heading = 'Senior Citizen Patient Statistics by Barangay';

    public ?string $filter = null;
    public ?int $fiscalYear = null;

    protected $listeners = ['fiscalYearChanged' => 'updateFiscalYear'];

    public function mount(): void
    {
        $this->fiscalYear = $this->fiscalYear ?? now()->year;
        // Set default filter if not already set
        if (!$this->filter) {
            $this->filter = now()->format('Y-m');
        }
    }

    public function updateFiscalYear(?int $fiscalYear): void
    {
        $this->fiscalYear = $fiscalYear;
    }

    protected function getData(): array
    {
        $service = new SeniorCitizenChartService();
        
        // Parse the selected month and year from filter (format: "YYYY-MM")
        [$year, $month] = explode('-', $this->filter);
        return $service->getSeniorCitizenPatientsByBarangay((int)$year, (int)$month);
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getFilters(): ?array
    {
        $filters = [];
        
        // Generate filters for the last 24 months
        for ($i = 0; $i < 24; $i++) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $label = $date->format('F Y');
            $filters[$key] = $label;
        }
        
        return $filters;
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'x' => [
                    'ticks' => [
                        'maxRotation' => 45,
                        'minRotation' => 45,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }

    public function getDescription(): ?string
    {
        if ($this->filter) {
            [$year, $month] = explode('-', $this->filter);
            $monthName = date('F', mktime(0, 0, 0, (int)$month, 1));
            return "Senior citizen patient distribution across barangays for {$monthName} {$year}";
        }
        return 'Senior citizen patient distribution by barangay.';
    }

    public function getHeading(): string|Htmlable|null
    {
        $user = auth()->user();

        if ($user->isBHW()) {
            return 'Senior Citizen Patient Statistics by Purok';
        }

        return 'Senior Citizen Patient Statistics by Barangay';
    }
}