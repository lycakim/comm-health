<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Services\SeniorCitizenChartService;

class SeniorCitizenChart extends ChartWidget
{
    protected static ?string $heading = 'Senior Citizen Patients Chart';

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
        $service = new SeniorCitizenChartService();

        return match($this->filter) {
            'gender_breakdown' => $service->getSeniorCitizenPatientsWithGenderBreakdown($this->fiscalYear),
            'monthly' => $service->getSeniorCitizenPatientsByMonth($this->fiscalYear),
            'comparison' => $service->getSeniorCitizenYearComparison($this->fiscalYear),
            'age_group' => $service->getSeniorCitizenPatientsByAgeGroup($this->fiscalYear),
            default => $service->getSeniorCitizenPatientsWithGenderBreakdown($this->fiscalYear),
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

    public function getDescription(): ?string
    {
        return 'Senior Citizens in the system.';
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