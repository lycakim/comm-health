<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\Patient;
use App\Models\Category;
use App\Models\Barangay;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MaternalChartService
{
    protected ?int $maternalCategoryId = null;

    public function __construct()
    {
        $this->maternalCategoryId = Category::findMaternal()?->id;
    }

    /**
     * Get maternal patients by barangay or purok for a specific month and year
     * Based on user role: BHW/Midwife = Purok, MHW = Barangay
     */
    public function getMaternalPatientsByBarangay(int $year, int $month, ?string $userRole = null): array
    {
        if (!$this->maternalCategoryId) {
            return $this->emptyBarangayDataset();
        }

        $user = auth()->user();
        $role = $userRole ?? $user?->role;
        
        if ($user->isBHW() || $user->isMidwife()) {
            return $this->getMaternalPatientsByPurok($year, $month, $user);
        }
        
        return $this->getMaternalPatientsByBarangayData($year, $month, $user, $role);
    }

    /**
     * Get maternal patients by purok (for BHW/Midwife users)
     */
    private function getMaternalPatientsByPurok(int $year, int $month, $user): array
    {
        // Get puroks for the user's assigned barangay
        $puroks = \App\Models\Purok::when($user?->barangay_id, function($query) use ($user) {
            return $query->where('barangay_id', $user->barangay_id);
        })->orderBy('name')->get();
        
        $totalData = [];
        
        foreach ($puroks as $purok) {
            $totalCount = Patient::where('purok_id', $purok->id)
                ->where('category_id', $this->maternalCategoryId)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();
            
            $totalData[] = $totalCount;
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Total Maternal Patients (Female)',
                    'data' => $totalData,
                    'backgroundColor' => 'rgba(236, 72, 153, 0.5)',
                    'borderColor' => 'rgb(236, 72, 153)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $puroks->pluck('name')->toArray(),
        ];
    }

    /**
     * Get maternal patients by barangay (for MHW users and admins)
     */
    private function getMaternalPatientsByBarangayData(int $year, int $month, $user, ?RoleEnum $role): array
    {
        $barangays = Barangay::when(
            $role === RoleEnum::MHO && $user?->barangay_id,
            function($query) use ($user) {
                return $query->where('id', $user->barangay_id);
            }
        )->orderBy('name')->get();
        
        $totalData = [];
        
        foreach ($barangays as $barangay) {
            $totalCount = Patient::where('barangay_id', $barangay->id)
                ->where('category_id', $this->maternalCategoryId)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();
            
            $totalData[] = $totalCount;
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Total Maternal Patients (Female)',
                    'data' => $totalData,
                    'backgroundColor' => 'rgba(236, 72, 153, 0.5)',
                    'borderColor' => 'rgb(236, 72, 153)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $barangays->pluck('name')->toArray(),
        ];
    }

    /**
     * Get maternal patients with gender breakdown (3 lines: Male, Female, Total)
     */
    public function getMaternalPatientsWithGenderBreakdown(int $year = null): array
    {
        $year = $year ?? now()->year;

        if (!$this->maternalCategoryId) {
            return $this->emptyDataset();
        }

        $maleData = $this->getMonthlyMaternalCounts($year, 'male');
        $femaleData = $this->getMonthlyMaternalCounts($year, 'female');
        
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
     * Get maternal patients by month
     */
    public function getMaternalPatientsByMonth(int $year = null): array
    {
        $year = $year ?? now()->year;

        if (!$this->maternalCategoryId) {
            return $this->emptyDataset();
        }

        $patients = Patient::where('category_id', $this->maternalCategoryId)
            ->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $monthlyData = $this->fillMissingMonths($patients);

        return [
            'datasets' => [
                [
                    'label' => "Maternal Patients {$year}",
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
     * Get maternal patients year comparison
     */
    public function getMaternalYearComparison(int $year = null): array
    {
        $year = $year ?? now()->year;
        $previousYear = $year - 1;

        if (!$this->maternalCategoryId) {
            return $this->emptyDataset();
        }

        $currentYearData = $this->getMonthlyMaternalCounts($year);
        $previousYearData = $this->getMonthlyMaternalCounts($previousYear);

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
     * Get maternal patients by age group
     */
    public function getMaternalPatientsByAgeGroup(int $year = null): array
    {
        $year = $year ?? now()->year;

        if (!$this->maternalCategoryId) {
            return $this->emptyDataset();
        }

        $patients = Patient::where('category_id', $this->maternalCategoryId)
            ->whereYear('created_at', $year)
            ->get();

        $ageGroups = [
            '15-19' => 0,
            '20-24' => 0,
            '25-29' => 0,
            '30-34' => 0,
            '35-39' => 0,
            '40+' => 0,
        ];

        foreach ($patients as $patient) {
            $age = Carbon::parse($patient->date_of_birth)->age;
            
            if ($age >= 15 && $age <= 19) {
                $ageGroups['15-19']++;
            } elseif ($age <= 24) {
                $ageGroups['20-24']++;
            } elseif ($age <= 29) {
                $ageGroups['25-29']++;
            } elseif ($age <= 34) {
                $ageGroups['30-34']++;
            } elseif ($age <= 39) {
                $ageGroups['35-39']++;
            } else {
                $ageGroups['40+']++;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Maternal Patients by Age',
                    'data' => array_values($ageGroups),
                    'backgroundColor' => [
                        'rgba(236, 72, 153, 0.6)',
                        'rgba(219, 39, 119, 0.6)',
                        'rgba(190, 24, 93, 0.6)',
                        'rgba(157, 23, 77, 0.6)',
                        'rgba(131, 24, 67, 0.6)',
                        'rgba(104, 20, 54, 0.6)',
                    ],
                    'borderColor' => [
                        'rgb(236, 72, 153)',
                        'rgb(219, 39, 119)',
                        'rgb(190, 24, 93)',
                        'rgb(157, 23, 77)',
                        'rgb(131, 24, 67)',
                        'rgb(104, 20, 54)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => array_keys($ageGroups),
        ];
    }

    /**
     * Get maternal statistics summary
     */
    public function getMaternalStatistics(int $year = null): array
    {
        $year = $year ?? now()->year;

        if (!$this->maternalCategoryId) {
            return [
                'total' => 0,
                'current_month' => 0,
                'previous_month' => 0,
                'growth_percentage' => 0,
            ];
        }

        $total = Patient::where('category_id', $this->maternalCategoryId)
            ->whereYear('created_at', $year)
            ->count();

        $currentMonth = Patient::where('category_id', $this->maternalCategoryId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $previousMonth = Patient::where('category_id', $this->maternalCategoryId)
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
     * Get total maternal patients
     */
    public function getTotalMaternalPatients(int $year = null): int
    {
        if (!$this->maternalCategoryId) {
            return 0;
        }

        $query = Patient::where('category_id', $this->maternalCategoryId);

        if ($year) {
            $query->whereYear('created_at', $year);
        }

        return $query->count();
    }

    /**
     * Get monthly maternal counts with optional gender filter
     */
    private function getMonthlyMaternalCounts(int $year, ?string $gender = null): array
    {
        if (!$this->maternalCategoryId) {
            return array_fill(1, 12, 0);
        }

        $query = Patient::where('category_id', $this->maternalCategoryId)
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