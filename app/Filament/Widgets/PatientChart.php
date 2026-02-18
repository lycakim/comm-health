<?php

namespace App\Filament\Widgets;

use App\Services\PatientChartService;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class PatientChart extends ChartWidget
{
    protected static ?string $heading = null;

    protected static string $view = 'filament.widgets.chart-with-filters';
    
    public ?string $filter = null;
    public ?string $genderFilter = 'all';
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
        $service = new PatientChartService();
        
        [$year, $month] = explode('-', $this->filter);
        
        return $service->getPatientsByBarangay(
            (int)$year, 
            (int)$month, 
            null, 
            $this->genderFilter ?? 'all'
        );
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getFilters(): ?array
    {
        return null;
    }

    public function getDateFilters(): array
    {
        $filters = [];
        for ($i = 0; $i < 24; $i++) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $label = $date->format('F Y');
            $filters[$key] = $label;
        }
        return $filters;
    }

    public function getGenderFilters(): array
    {
        return [
            'all' => '👥 All Residents',
            'male' => '👨 Male Only',
            'female' => '👩 Female Only',
            'children' => '👶 Children (0-17)',
        ];
    }

    public function updatedFilter($value): void
    {
        $this->cachedData = null;
    }

    public function updatedGenderFilter($value): void
    {
        $this->cachedData = null;
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
        $user = auth()->user();
        $locationLabel = $user->isBHW() ? 'puroks' : 'barangays';
        
        if ($this->filter) {
            [$year, $month] = explode('-', $this->filter);
            $monthName = date('F', mktime(0, 0, 0, (int)$month, 1));
            return "Resident distribution across {$locationLabel} for {$monthName} {$year}";
        }
        return "Resident distribution by {$locationLabel}.";
    }

    public function getHeading(): string|Htmlable|null
    {
        $user = auth()->user();

        if ($user->isBHW()) {
            return 'Resident Statistics by Purok';
        }

        return 'Resident Statistics by Barangay';
    }
}