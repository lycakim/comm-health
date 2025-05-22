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