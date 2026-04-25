<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\Patient;
use App\Models\Barangay;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SeniorCitizenChartService
{
    /** Minimum age for senior citizens: 60 years */
    private const AGE_MIN = 60;

    /**
     * Get senior citizen patients by barangay or purok for a specific month and year.
     * Uses birth_date to calculate age as of the selected date (not category_id or created_at).
     * Based on user role: BHW/Midwife = Purok, MHW = Barangay
     */
    public function getSeniorCitizenPatientsByBarangay(int $year, int $month, ?string $userRole = null, string $genderFilter = 'all'): array
    {
        $user = auth()->user();
        $role = $userRole ?? $user?->role;
        
        if ($user->isBHW() || $user->isMidwife()) {
            return $this->getSeniorCitizenPatientsByPurok($year, $month, $user, $genderFilter);
        }
        
        return $this->getSeniorCitizenPatientsByBarangayData($year, $month, $user, $role, $genderFilter);
    }

    /** Reference date for age calculation (first day of selected month) */
    private function getReferenceDate(int $year, int $month): string
    {
        return sprintf('%04d-%02d-01', $year, $month);
    }

    /** Base query for senior citizens (age >= 60 as of reference date) */
    private function seniorAgeQuery(string $refDate)
    {
        return Patient::whereNotNull('birth_date')
            ->whereRaw('TIMESTAMPDIFF(YEAR, birth_date, ?) >= ?', [$refDate, self::AGE_MIN]);
    }

    /** Base query for senior citizens (age >= 60 as of their registration date) */
    private function seniorAtRegistrationQuery()
    {
        return Patient::whereNotNull('birth_date')
            ->whereRaw('TIMESTAMPDIFF(YEAR, birth_date, created_at) >= ?', [self::AGE_MIN]);
    }

    /**
     * Get senior citizen patients by purok (for BHW/Midwife users)
     */
    private function getSeniorCitizenPatientsByPurok(int $year, int $month, $user, string $genderFilter = 'all'): array
    {
        // BHW/Midwife with no barangay_id: see no data
        if (!$user?->barangay_id) {
            return [
                'datasets' => [
                    ['label' => 'Male', 'data' => [], 'backgroundColor' => 'rgba(59, 130, 246, 0.5)', 'borderColor' => 'rgb(59, 130, 246)', 'borderWidth' => 1],
                    ['label' => 'Female', 'data' => [], 'backgroundColor' => 'rgba(236, 72, 153, 0.5)', 'borderColor' => 'rgb(236, 72, 153)', 'borderWidth' => 1],
                ],
                'labels' => [],
            ];
        }
        $refDate = $this->getReferenceDate($year, $month);
        $puroks = \App\Models\Purok::where('barangay_id', $user->barangay_id)
            ->orderBy('name')
            ->get();
        
        $maleData = [];
        $femaleData = [];
        $collectMale = in_array($genderFilter, ['all', 'male']);
        $collectFemale = in_array($genderFilter, ['all', 'female']);
        
        foreach ($puroks as $purok) {
            if ($collectMale) {
                $maleCount = $this->seniorAgeQuery($refDate)
                    ->where('purok_id', $purok->id)
                    ->where('sex', 'male')
                    ->count();
                $maleData[] = $maleCount;
            }
            if ($collectFemale) {
                $femaleCount = $this->seniorAgeQuery($refDate)
                    ->where('purok_id', $purok->id)
                    ->where('sex', 'female')
                    ->count();
                $femaleData[] = $femaleCount;
            }
        }
        
        $datasets = [];
        if ($collectMale) {
            $datasets[] = [
                'label' => 'Male',
                'data' => $maleData,
                'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                'borderColor' => 'rgb(59, 130, 246)',
                'borderWidth' => 1,
            ];
        }
        if ($collectFemale) {
            $datasets[] = [
                'label' => 'Female',
                'data' => $femaleData,
                'backgroundColor' => 'rgba(236, 72, 153, 0.5)',
                'borderColor' => 'rgb(236, 72, 153)',
                'borderWidth' => 1,
            ];
        }
        
        return [
            'datasets' => $datasets,
            'labels' => $puroks->pluck('name')->toArray(),
        ];
    }

    /**
     * Get senior citizen patients by barangay (for MHW users and admins)
     */
    private function getSeniorCitizenPatientsByBarangayData(int $year, int $month, $user, ?RoleEnum $role, string $genderFilter = 'all'): array
    {
        $refDate = $this->getReferenceDate($year, $month);
        $barangays = Barangay::when(
            $role === RoleEnum::MHO && $user?->barangay_id,
            function($query) use ($user) {
                return $query->where('id', $user->barangay_id);
            }
        )->orderBy('name')->get();
        
        $maleData = [];
        $femaleData = [];
        $collectMale = in_array($genderFilter, ['all', 'male']);
        $collectFemale = in_array($genderFilter, ['all', 'female']);
        
        foreach ($barangays as $barangay) {
            if ($collectMale) {
                $maleCount = $this->seniorAgeQuery($refDate)
                    ->where('barangay_id', $barangay->id)
                    ->where('sex', 'male')
                    ->count();
                $maleData[] = $maleCount;
            }
            if ($collectFemale) {
                $femaleCount = $this->seniorAgeQuery($refDate)
                    ->where('barangay_id', $barangay->id)
                    ->where('sex', 'female')
                    ->count();
                $femaleData[] = $femaleCount;
            }
        }
        
        $datasets = [];
        if ($collectMale) {
            $datasets[] = [
                'label' => 'Male',
                'data' => $maleData,
                'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                'borderColor' => 'rgb(59, 130, 246)',
                'borderWidth' => 1,
            ];
        }
        if ($collectFemale) {
            $datasets[] = [
                'label' => 'Female',
                'data' => $femaleData,
                'backgroundColor' => 'rgba(236, 72, 153, 0.5)',
                'borderColor' => 'rgb(236, 72, 153)',
                'borderWidth' => 1,
            ];
        }
        
        return [
            'datasets' => $datasets,
            'labels' => $barangays->pluck('name')->toArray(),
        ];
    }

    /**
     * Get senior citizen patients with gender breakdown (3 lines: Male, Female, Total)
     */
    public function getSeniorCitizenPatientsWithGenderBreakdown(int $year = null): array
    {
        $year = $year ?? now()->year;

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

        $patients = $this->seniorAtRegistrationQuery()
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

        $patients = $this->seniorAtRegistrationQuery()
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
            $age = Carbon::parse($patient->birth_date)->age;
            
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

        $total = $this->seniorAtRegistrationQuery()
            ->whereYear('created_at', $year)
            ->count();

        $currentMonth = $this->seniorAtRegistrationQuery()
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $previousMonth = $this->seniorAtRegistrationQuery()
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
        $query = $this->seniorAtRegistrationQuery();

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
        $query = $this->seniorAtRegistrationQuery()
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

}
