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

    // protected function getData(): array
    // {
    //     $user = auth()->user();
        
    //     // If user is BHW, only show data for their assigned barangay
    //     if ($user->role === 'bhw' && $user->barangay_id) {
    //         // Filter data by user's barangay
    //         return $this->getBarangaySpecificData($user->barangay_id);
    //     }
        
    //     // For patients, only show their own data
    //     if (in_array($user->role, ['resident', 'patient'])) {
    //         return $this->getPatientSpecificData($user->id);
    //     }
        
    //     // For other roles, show broader data
    //     return $this->getAllData();
    // }

    protected function getType(): string
    {
        return 'bar';
    }
}