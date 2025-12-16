<?php

namespace App\Filament\Widgets;

use App\Models\Program;
use App\Models\Category;
use Filament\Widgets\ChartWidget;

class HealthServicesChart extends ChartWidget
{
    protected static ?string $heading = 'Health Services Overview';

    protected static ?string $description = 'Distribution of health services provided in the last 30 days';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $serviceTypes = Category::pluck('name', 'id')->toArray();
        $data = [];
        
        foreach ($serviceTypes as $key => $type) {
            $data[] = Program::where('category_id', $key)
                ->where('created_at', '>', now()->subDays(30))
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Health Services',
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