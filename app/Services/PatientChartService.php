<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\Patient;
use App\Models\Barangay;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PatientChartService
{
    /**
     * Get patient count by barangay or purok for a specific month and year
     * Based on user role: BHW = Purok, MHW = Barangay
     */
    public function getPatientsByBarangay(int $year, int $month, ?string $userRole = null): array
    {
        $user = auth()->user();
        $role = $userRole ?? $user?->role;
        
        // Determine if we're filtering by purok (BHW) or barangay (MHW)
        if ($user->isBHW()) {
            return $this->getPatientsByPurok($year, $month, $user);
        }
        
        // Default: MHW or admin - show by barangay
        return $this->getPatientsByBarangayData($year, $month, $user, $role);
    }

    /**
     * Get patients by purok (for BHW users)
     */
    private function getPatientsByPurok(int $year, int $month, $user): array
    {
        // Get puroks - adjust the model name and relationship as needed
        // Assuming you have a Purok model or puroks within user's barangay
        $puroks = \App\Models\Purok::when($user?->barangay_id, function($query) use ($user) {
            return $query->where('barangay_id', $user->barangay_id);
        })->orderBy('name')->get();
        
        $maleData = [];
        $femaleData = [];
        $childrenData = [];
        
        foreach ($puroks as $purok) {
            // Get male count
            $maleCount = Patient::where('purok_id', $purok->id)
                ->where('sex', 'male')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();
            
            // Get female count
            $femaleCount = Patient::where('purok_id', $purok->id)
                ->where('sex', 'female')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();
            
            // Get children count
            $childrenCount = Patient::where('purok_id', $purok->id)
                ->whereHas('category', function($query) {
                    $query->where('name', 'LIKE', '%child%')
                          ->orWhere('name', 'LIKE', '%pediatric%');
                })
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();
            
            $maleData[] = $maleCount;
            $femaleData[] = $femaleCount;
            $childrenData[] = $childrenCount;
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Male',
                    'data' => $maleData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Female',
                    'data' => $femaleData,
                    'backgroundColor' => 'rgba(236, 72, 153, 0.5)',
                    'borderColor' => 'rgb(236, 72, 153)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Children',
                    'data' => $childrenData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $puroks->pluck('name')->toArray(),
        ];
    }

    /**
     * Get patients by barangay (for MHW users and admins)
     */
    private function getPatientsByBarangayData(int $year, int $month, $user, ?RoleEnum $role): array
    {
        $barangays = Barangay::when(
            $role === RoleEnum::MHO && $user?->barangay_id,
            function($query) use ($user) {
                return $query->where('id', $user->barangay_id);
            }
        )->orderBy('name')->get();
        
        $maleData = [];
        $femaleData = [];
        $childrenData = [];
        
        foreach ($barangays as $barangay) {
            // Get male count
            $maleCount = Patient::where('barangay_id', $barangay->id)
                ->where('sex', 'male')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();
            
            // Get female count
            $femaleCount = Patient::where('barangay_id', $barangay->id)
                ->where('sex', 'female')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();
            
            // Get children count
            $childrenCount = Patient::where('barangay_id', $barangay->id)
                ->whereHas('category', function($query) {
                    $query->where('name', 'LIKE', '%child%')
                          ->orWhere('name', 'LIKE', '%pediatric%');
                })
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();
            
            $maleData[] = $maleCount;
            $femaleData[] = $femaleCount;
            $childrenData[] = $childrenCount;
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Male',
                    'data' => $maleData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Female',
                    'data' => $femaleData,
                    'backgroundColor' => 'rgba(236, 72, 153, 0.5)',
                    'borderColor' => 'rgb(236, 72, 153)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Children',
                    'data' => $childrenData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $barangays->pluck('name')->toArray(),
        ];
    }

    /**
     * Get patient count by barangay with gender breakdown
     */
    public function getPatientsByBarangayWithGender(int $year, int $month): array
    {
        $barangays = Barangay::orderBy('name')->get();
        
        $maleData = [];
        $femaleData = [];
        
        foreach ($barangays as $barangay) {
            $maleCount = Patient::where('barangay_id', $barangay->id)
                ->where('sex', 'male')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();
            
            $femaleCount = Patient::where('barangay_id', $barangay->id)
                ->where('sex', 'female')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();
            
            $maleData[] = $maleCount;
            $femaleData[] = $femaleCount;
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Male',
                    'data' => $maleData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Female',
                    'data' => $femaleData,
                    'backgroundColor' => 'rgba(236, 72, 153, 0.5)',
                    'borderColor' => 'rgb(236, 72, 153)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $barangays->pluck('name')->toArray(),
        ];
    }

    /**
     * Get patient count with male, female, and total combined
     */
    public function getPatientsWithGenderBreakdown(int $year = null): array
    {
        $year = $year ?? now()->year;

        $maleData = $this->getMonthlyPatientCounts($year, 'male');
        $femaleData = $this->getMonthlyPatientCounts($year, 'female');
        
        // Calculate combined totals
        $combinedData = [];
        for ($month = 1; $month <= 12; $month++) {
            $combinedData[$month] = $maleData[$month] + $femaleData[$month];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Male',
                    'data' => array_values($maleData),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'tension' => 0.3,
                    'fill' => true,
                ],
                [
                    'label' => 'Female',
                    'data' => array_values($femaleData),
                    'backgroundColor' => 'rgba(236, 72, 153, 0.1)',
                    'borderColor' => 'rgb(236, 72, 153)',
                    'borderWidth' => 2,
                    'tension' => 0.3,
                    'fill' => true,
                ],
                [
                    'label' => 'Total',
                    'data' => array_values($combinedData),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 3,
                    'tension' => 0.3,
                    'fill' => false,
                    'borderDash' => [5, 5], // Makes it a dashed line to differentiate
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }

    /**
     * Get patient count data grouped by month for a given year
     */
    public function getPatientsByMonth(int $year = null): array
    {
        $year = $year ?? now()->year;
        
        $patients = Patient::whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $monthlyData = $this->fillMissingMonths($patients);

        return [
            'datasets' => [
                [
                    'label' => "Patients {$year}",
                    'data' => array_values($monthlyData),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'tension' => 0.3,
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }

    /**
     * Get patient count comparison between current and previous year
     */
    public function getPatientYearComparison(int $year = null): array
    {
        $year = $year ?? now()->year;
        $previousYear = $year - 1;

        $currentYearData = $this->getMonthlyPatientCounts($year);
        $previousYearData = $this->getMonthlyPatientCounts($previousYear);

        return [
            'datasets' => [
                [
                    'label' => "{$previousYear}",
                    'data' => array_values($previousYearData),
                    'backgroundColor' => 'rgba(156, 163, 175, 0.5)',
                    'borderColor' => 'rgb(156, 163, 175)',
                    'tension' => 0.3,
                ],
                [
                    'label' => "{$year}",
                    'data' => array_values($currentYearData),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'tension' => 0.3,
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }

    /**
     * Get patient count by gender for a given year (separate lines)
     */
    public function getPatientsByGender(int $year = null): array
    {
        $year = $year ?? now()->year;

        $maleData = $this->getMonthlyPatientCounts($year, 'male');
        $femaleData = $this->getMonthlyPatientCounts($year, 'female');

        return [
            'datasets' => [
                [
                    'label' => 'Male',
                    'data' => array_values($maleData),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Female',
                    'data' => array_values($femaleData),
                    'backgroundColor' => 'rgba(236, 72, 153, 0.5)',
                    'borderColor' => 'rgb(236, 72, 153)',
                    'tension' => 0.3,
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }

    /**
     * Get patient count by age group for a given year
     */
    public function getPatientsByAgeGroup(int $year = null): array
    {
        $year = $year ?? now()->year;

        $patients = Patient::whereYear('created_at', $year)->get();

        $ageGroups = [
            '0-17' => 0,
            '18-35' => 0,
            '36-50' => 0,
            '51-65' => 0,
            '65+' => 0,
        ];

        foreach ($patients as $patient) {
            $age = Carbon::parse($patient->date_of_birth)->age;
            
            if ($age < 18) {
                $ageGroups['0-17']++;
            } elseif ($age <= 35) {
                $ageGroups['18-35']++;
            } elseif ($age <= 50) {
                $ageGroups['36-50']++;
            } elseif ($age <= 65) {
                $ageGroups['51-65']++;
            } else {
                $ageGroups['65+']++;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Patients by Age Group',
                    'data' => array_values($ageGroups),
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.5)',
                        'rgba(16, 185, 129, 0.5)',
                        'rgba(245, 158, 11, 0.5)',
                        'rgba(239, 68, 68, 0.5)',
                        'rgba(139, 92, 246, 0.5)',
                    ],
                ],
            ],
            'labels' => array_keys($ageGroups),
        ];
    }

    /**
     * Get total patient count
     */
    public function getTotalPatients(int $year = null): int
    {
        $query = Patient::query();

        if ($year) {
            $query->whereYear('created_at', $year);
        }

        return $query->count();
    }

    /**
     * Get monthly patient counts with optional gender filter
     */
    private function getMonthlyPatientCounts(int $year, ?string $gender = null): array
    {
        $query = Patient::whereYear('created_at', $year);

        if ($gender) {
            $query->where('sex', $gender);
        }

        $patients = $query->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        return $this->fillMissingMonths($patients);
    }

    /**
     * Fill missing months with zero values
     */
    private function fillMissingMonths(Collection $data): array
    {
        $monthlyData = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthlyData[$month] = $data->get($month, 0);
        }

        return $monthlyData;
    }
}