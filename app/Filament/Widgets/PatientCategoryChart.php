<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Patient;
use Filament\Widgets\ChartWidget;

class PatientCategoryChart extends ChartWidget
{
    protected static ?string $heading = 'Patients Category Preview';

    protected static ?string $description = 'Distribution of patients by category';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $serviceTypes = Category::pluck('name', 'id')->toArray();
        $data = [];
        
        foreach ($serviceTypes as $key => $type) {
            $data[] = Patient::where('category_id', $key)
                ->where('created_at', '>', now()->subDays(30))
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Categories',
                    'data' => $data,
                    'backgroundColor' => '#0f766e',
                ],
            ],
            'labels' => array_values($serviceTypes),
        ];        
    }

    protected function getType(): string
    {
        return 'bar';
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
}