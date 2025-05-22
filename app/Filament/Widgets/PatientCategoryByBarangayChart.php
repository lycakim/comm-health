<?php

namespace App\Filament\Widgets;

use App\Models\Barangay;
use App\Models\Patient;
use App\Models\Category;
use Filament\Widgets\ChartWidget;

class PatientCategoryByBarangayChart extends ChartWidget
{
    protected static ?string $heading = 'Patients Category By Barangay';
    protected static ?string $description = 'Distribution of patients by category';

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $brgys = Barangay::pluck('name', 'id')->toArray();
        $serviceTypes = Category::pluck('name', 'id')->toArray();
        $datasets = [];
        
        $colors = [
            '#0f766e', // teal-700
            '#2563eb', // blue-600
            '#7c3aed', // violet-600
            '#ea580c', // orange-600
            '#16a34a', // green-600
            '#dc2626', // red-600
            '#84cc16', // lime-500
            '#6366f1', // indigo-500
            '#ec4899', // pink-500
            '#8b5cf6', // purple-500
        ];
        
        $colorIndex = 0;
        
        foreach ($serviceTypes as $categoryId => $categoryName) {
            $categoryData = [];
            
            foreach ($brgys as $barangayId => $barangayName) {
                $count = Patient::where('category_id', $categoryId)
                    ->where('barangay_id', $barangayId)
                    ->where('created_at', '>', now()->subDays(30))
                    ->count();
                    
                $categoryData[] = $count;
            }
            
            $color = $colors[$colorIndex % count($colors)];
            $colorIndex++;
            
            $datasets[] = [
                'label' => $categoryName,
                'data' => $categoryData,
                'backgroundColor' => $color,
            ];
        }
        
        return [
            'datasets' => $datasets,
            'labels' => array_values($brgys),
        ];        
    }

    protected function getType(): string
    {
        return 'bar';
    }
}