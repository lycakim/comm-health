<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\Patient;
use App\Models\Barangay;
use App\Models\Category;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SeniorCitizenChartService
{
    protected ?int $seniorCitizenCategoryId = null;

    public function __construct()
    {
        $this->seniorCitizenCategoryId = Category::findByAge(60)?->id;
    }

    /**
     * Get senior citizen patients by barangay or purok for a specific month and year
     * Based on user role: BHW/Midwife = Purok, MHW = Barangay
     */
    public function getSeniorCitizenPatientsByBarangay(int $year, int $month, ?string $userRole = null): array
    {
        if (!$this->seniorCitizenCategoryId) {
            return $this->emptyBarangayDataset();
        }

        $user = auth()->user();
        $role = $userRole ?? $user?->role;
        
        if ($user->isBHW() || $user->isMidwife()) {
            return $this->getSeniorCitizenPatientsByPurok($year, $month, $user);
        }
        
        return $this->getSeniorCitizenPatientsByBarangayData($year, $month, $user, $role);
    }

    /**
     * Get senior citizen patients by purok (for BHW/Midwife users)
     */
    private function getSeniorCitizenPatientsByPurok(int $year, int $month, $user): array
    {
        // Get puroks for the user's assigned barangay
        $puroks = \App\Models\Purok::when($user?->barangay_id, function($query) use ($user) {
            return $query->where('barangay_id', $user->barangay_id);
        })->orderBy('name')->get();
        
        $maleData = [];
        $femaleData = [];
        
        foreach ($puroks as $purok) {
            $maleCount = Patient::where('purok_id', $purok->id)
                ->where('category_id', $this->seniorCitizenCategoryId)
                ->where('sex', 'male')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();
            
            $femaleCount = Patient::where('purok_id', $purok->id)
                ->where('category_id', $this->seniorCitizenCategoryId)
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
            'labels' => $puroks->pluck('name')->toArray(),
        ];
    }

    /**
     * Get senior citizen patients by barangay (for MHW users and admins)
     */
    private function getSeniorCitizenPatientsByBarangayData(int $year, int $month, $user, ?RoleEnum $role): array
    {
        $barangays = Barangay::when(
            $role === RoleEnum::MHO && $user?->barangay_id,
            function($query) use ($user) {
                return $query->where('id', $user->barangay_id);
            }
        )->orderBy('name')->get();
        
        $maleData = [];
        $femaleData = [];
        
        foreach ($barangays as $barangay) {
            $maleCount = Patient::where('barangay_id', $barangay->id)
                ->where('category_id', $this->seniorCitizenCategoryId)
                ->where('sex', 'male')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();
            
            $femaleCount = Patient::where('barangay_id', $barangay->id)
                ->where('category_id', $this->seniorCitizenCategoryId)
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
     * Get senior citizen patients with gender breakdown (3 lines: Male, Female, Total)
     */
    public function getSeniorCitizenPatientsWithGenderBreakdown(int $year = null): array
    {
        $year = $year ?? now()->year;

        if (!$this->seniorCitizenCategoryId) {
            return $this->emptyDataset();
        }

        $maleData = $this->getMonthlySeniorCitizenCounts($year, 'male');
        $femaleData = $this->getMonthlySeniorCitizenCounts($year, 'female');
        
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
                    'borderDash' => [5, 5],
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }

    /**
     * Get senior citizen patients by month
     */
    public function getSeniorCitizenPatientsByMonth(int $year = null): array
    {
        $year = $year ?? now()->year;

        if (!$this->seniorCitizenCategoryId) {
            return $this->emptyDataset();
        }

        $patients = Patient::where('category_id', $this->seniorCitizenCategoryId)
            ->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $monthlyData = $this->fillMissingMonths($patients);

        return [
            'datasets' => [
                [
                    'label' => "Senior Citizen Patients {$year}",
                    'data' => array_values($monthlyData),
                    'backgroundColor' => 'rgba(236, 72, 153, 0.5)',
                    'borderColor' => 'rgb(236, 72, 153)',
                    'borderWidth' => 2,
                    'tension' => 0.3,
                    'fill' => true,
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }

    /**
     * Get senior citizen patients year comparison
     */
    public function getSeniorCitizenYearComparison(int $year = null): array
    {
        $year = $year ?? now()->year;
        $previousYear = $year - 1;

        if (!$this->seniorCitizenCategoryId) {
            return $this->emptyDataset();
        }

        $currentYearData = $this->getMonthlySeniorCitizenCounts($year);
        $previousYearData = $this->getMonthlySeniorCitizenCounts($previousYear);

        return [
            'datasets' => [
                [
                    'label' => "{$previousYear}",
                    'data' => array_values($previousYearData),
                    'backgroundColor' => 'rgba(156, 163, 175, 0.5)',
                    'borderColor' => 'rgb(156, 163, 175)',
                    'borderWidth' => 2,
                    'tension' => 0.3,
                ],
                [
                    'label' => "{$year}",
                    'data' => array_values($currentYearData),
                    'backgroundColor' => 'rgba(236, 72, 153, 0.5)',
                    'borderColor' => 'rgb(236, 72, 153)',
                    'borderWidth' => 2,
                    'tension' => 0.3,
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }

    /**
     * Get senior citizen patients by age group
     */
    public function getSeniorCitizenPatientsByAgeGroup(int $year = null): array
    {
        $year = $year ?? now()->year;

        if (!$this->seniorCitizenCategoryId) {
            return $this->emptyDataset();
        }

        $patients = Patient::where('category_id', $this->seniorCitizenCategoryId)
            ->whereYear('created_at', $year)
            ->get();

        $ageGroups = [
            '60-64' => 0,
            '65-69' => 0,
            '70-74' => 0,
            '75-79' => 0,
            '80-84' => 0,
            '85+' => 0,
        ];

        foreach ($patients as $patient) {
            $age = Carbon::parse($patient->date_of_birth)->age;
            
            if ($age >= 60 && $age <= 64) {
                $ageGroups['60-64']++;
            } elseif ($age <= 69) {
                $ageGroups['65-69']++;
            } elseif ($age <= 74) {
                $ageGroups['70-74']++;
            } elseif ($age <= 79) {
                $ageGroups['75-79']++;
            } elseif ($age <= 84) {
                $ageGroups['80-84']++;
            } else {
                $ageGroups['85+']++;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Senior Citizen Patients by Age',
                    'data' => array_values($ageGroups),
                    'backgroundColor' => [
                        'rgba(139, 92, 246, 0.6)',
                        'rgba(124, 58, 237, 0.6)',
                        'rgba(109, 40, 217, 0.6)',
                        'rgba(91, 33, 182, 0.6)',
                        'rgba(76, 29, 149, 0.6)',
                        'rgba(59, 7, 100, 0.6)',
                    ],
                    'borderColor' => [
                        'rgb(139, 92, 246)',
                        'rgb(124, 58, 237)',
                        'rgb(109, 40, 217)',
                        'rgb(91, 33, 182)',
                        'rgb(76, 29, 149)',
                        'rgb(59, 7, 100)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => array_keys($ageGroups),
        ];
    }

    /**
     * Get senior citizen statistics summary
     */
    public function getSeniorCitizenStatistics(int $year = null): array
    {
        $year = $year ?? now()->year;

        if (!$this->seniorCitizenCategoryId) {
            return [
                'total' => 0,
                'current_month' => 0,
                'previous_month' => 0,
                'growth_percentage' => 0,
            ];
        }

        $total = Patient::where('category_id', $this->seniorCitizenCategoryId)
            ->whereYear('created_at', $year)
            ->count();

        $currentMonth = Patient::where('category_id', $this->seniorCitizenCategoryId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $previousMonth = Patient::where('category_id', $this->seniorCitizenCategoryId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->count();

        $growthPercentage = $previousMonth > 0 
            ? (($currentMonth - $previousMonth) / $previousMonth) * 100 
            : 0;

        return [
            'total' => $total,
            'current_month' => $currentMonth,
            'previous_month' => $previousMonth,
            'growth_percentage' => round($growthPercentage, 2),
        ];
    }

    /**
     * Get total senior citizen patients
     */
    public function getTotalSeniorCitizenPatients(int $year = null): int
    {
        if (!$this->seniorCitizenCategoryId) {
            return 0;
        }

        $query = Patient::where('category_id', $this->seniorCitizenCategoryId);

        if ($year) {
            $query->whereYear('created_at', $year);
        }

        return $query->count();
    }

    /**
     * Get monthly senior citizen counts with optional gender filter
     */
    private function getMonthlySeniorCitizenCounts(int $year, ?string $gender = null): array
    {
        if (!$this->seniorCitizenCategoryId) {
            return array_fill(1, 12, 0);
        }

        $query = Patient::where('category_id', $this->seniorCitizenCategoryId)
            ->whereYear('created_at', $year);

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

    /**
     * Return empty dataset when category not found
     */
    private function emptyDataset(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'No Data',
                    'data' => array_fill(0, 12, 0),
                    'backgroundColor' => 'rgba(156, 163, 175, 0.5)',
                    'borderColor' => 'rgb(156, 163, 175)',
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }

    /**
     * Return empty barangay dataset when category not found
     */
    private function emptyBarangayDataset(): array
    {
        $barangays = Barangay::orderBy('name')->get();
        
        return [
            'datasets' => [
                [
                    'label' => 'No Data',
                    'data' => array_fill(0, $barangays->count(), 0),
                    'backgroundColor' => 'rgba(156, 163, 175, 0.5)',
                    'borderColor' => 'rgb(156, 163, 175)',
                ],
            ],
            'labels' => $barangays->pluck('name')->toArray(),
        ];
    }
}