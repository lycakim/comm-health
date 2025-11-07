<?php

namespace App\Filament\Widgets;

use App\Services\ChildrenChartService;
use Filament\Widgets\ChartWidget;

class ChildrenChart extends ChartWidget
{
    protected static ?string $heading = 'Children Patients Chart';

    public ?string $filter = 'gender_breakdown';
    public int $fiscalYear;

    protected $listeners = ['fiscalYearChanged' => 'updateFiscalYear'];

    public function mount(): void
    {
        $this->fiscalYear = $this->fiscalYear ?? now()->year;
    }

    public function updateFiscalYear($fiscalYear): void
    {
        $this->fiscalYear = $fiscalYear;
    }

    protected function getData(): array
    {
        $service = new ChildrenChartService();

        return match($this->filter) {
            'gender_breakdown' => $service->getChildrenPatientsWithGenderBreakdown($this->fiscalYear),
            'monthly' => $service->getChildrenPatientsByMonth($this->fiscalYear),
            'comparison' => $service->getChildrenYearComparison($this->fiscalYear),
            'age_group' => $service->getChildrenPatientsByAgeGroup($this->fiscalYear),
            default => $service->getChildrenPatientsWithGenderBreakdown($this->fiscalYear),
        };
    }

    protected function getType(): string
    {
        return $this->filter === 'age_group' ? 'bar' : 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'gender_breakdown' => 'Gender Breakdown',
            'monthly' => 'Monthly Total',
            'comparison' => 'Year Comparison',
            'age_group' => 'By Age Group',
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'title' => [
                    'display' => true,
                    'text' => 'Maternal Health Statistics - ' . $this->fiscalYear,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}