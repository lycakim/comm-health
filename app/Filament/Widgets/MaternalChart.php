<?php

namespace App\Filament\Widgets;

use App\Services\MaternalChartService;
use Filament\Widgets\ChartWidget;

class MaternalChart extends ChartWidget
{
    protected static ?string $heading = 'Maternal Patient Statistics';
    
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
        $service = new MaternalChartService();

        return match($this->filter) {
            'gender_breakdown' => $service->getMaternalPatientsWithGenderBreakdown($this->fiscalYear),
            'monthly' => $service->getMaternalPatientsByMonth($this->fiscalYear),
            'comparison' => $service->getMaternalYearComparison($this->fiscalYear),
            'age_group' => $service->getMaternalPatientsByAgeGroup($this->fiscalYear),
            default => $service->getMaternalPatientsWithGenderBreakdown($this->fiscalYear),
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