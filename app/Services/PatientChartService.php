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
     * Based on user role: BHW/Midwife = Purok, MHW = Barangay
     */
    public function getPatientsByBarangay(int $year, int $month, ?string $userRole = null, string $genderFilter = 'all'): array
    {
        $user = auth()->user();
        $role = $userRole ?? $user?->role;
        
        // Determine if we're filtering by purok (BHW/Midwife) or barangay (MHW)
        if ($user->isBHW() || $user->isMidwife()) {
            return $this->getPatientsByPurok($year, $month, $user, $genderFilter);
        }
        
        // Default: MHW or admin - show by barangay
        return $this->getPatientsByBarangayData($year, $month, $user, $role, $genderFilter);
    }

    /**
     * Get patients by purok (for BHW/Midwife users)
     * Optimized: Uses single aggregated query instead of N queries per purok
     */
    private function getPatientsByPurok(int $year, int $month, $user, string $genderFilter = 'all'): array
    {
        // BHW/Midwife with no barangay_id: see no data
        if (!$user?->barangay_id) {
            return ['datasets' => [], 'labels' => []];
        }
        // Get puroks for the user's assigned barangay
        $puroks = \App\Models\Purok::where('barangay_id', $user->barangay_id)
            ->orderBy('name')
            ->get();
        
        if ($puroks->isEmpty()) {
            return ['datasets' => [], 'labels' => []];
        }
        
        $purokIds = $puroks->pluck('id')->toArray();
        
        // Determine which datasets to collect
        $collectMale = in_array($genderFilter, ['all', 'male']);
        $collectFemale = in_array($genderFilter, ['all', 'female']);
        $collectChildren = in_array($genderFilter, ['all', 'children']);
        
        // Build SELECT clause with conditional aggregation
        $selectClause = 'purok_id';
        if ($collectMale) {
            $selectClause .= ', SUM(CASE WHEN sex = \'male\' THEN 1 ELSE 0 END) as male_count';
        }
        if ($collectFemale) {
            $selectClause .= ', SUM(CASE WHEN sex = \'female\' THEN 1 ELSE 0 END) as female_count';
        }
        if ($collectChildren) {
            $selectClause .= ', SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 18 THEN 1 ELSE 0 END) as children_count';
        }
        
        // Single aggregated query for all puroks
        $counts = Patient::whereIn('purok_id', $purokIds)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->selectRaw($selectClause)
            ->groupBy('purok_id')
            ->get()
            ->keyBy('purok_id');
        
        // Build data arrays matching purok order
        $maleData = [];
        $femaleData = [];
        $childrenData = [];
        
        foreach ($puroks as $purok) {
            $count = $counts->get($purok->id);
            if ($collectMale) {
                $maleData[] = $count ? (int)$count->male_count : 0;
            }
            if ($collectFemale) {
                $femaleData[] = $count ? (int)$count->female_count : 0;
            }
            if ($collectChildren) {
                $childrenData[] = $count ? (int)$count->children_count : 0;
            }
        }
        
        // Build datasets
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
        
        if ($collectChildren) {
            $datasets[] = [
                'label' => 'Children (0-17)',
                'data' => $childrenData,
                'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                'borderColor' => 'rgb(16, 185, 129)',
                'borderWidth' => 1,
            ];
        }
        
        return [
            'datasets' => $datasets,
            'labels' => $puroks->pluck('name')->toArray(),
        ];
    }

    /**
     * Get patients by barangay (for MHW users and admins)
     * Optimized: Uses single aggregated query instead of N queries per barangay
     */
    private function getPatientsByBarangayData(int $year, int $month, $user, $role, string $genderFilter = 'all'): array
    {
        // Get all barangays
        $barangays = \App\Models\Barangay::orderBy('name')->get();
        
        if ($barangays->isEmpty()) {
            return ['datasets' => [], 'labels' => []];
        }
        
        $barangayIds = $barangays->pluck('id')->toArray();
        
        // Determine which datasets to collect
        $collectMale = in_array($genderFilter, ['all', 'male']);
        $collectFemale = in_array($genderFilter, ['all', 'female']);
        $collectChildren = in_array($genderFilter, ['all', 'children']);
        
        // Build SELECT clause with conditional aggregation
        $selectClause = 'barangay_id';
        if ($collectMale) {
            $selectClause .= ', SUM(CASE WHEN sex = \'male\' THEN 1 ELSE 0 END) as male_count';
        }
        if ($collectFemale) {
            $selectClause .= ', SUM(CASE WHEN sex = \'female\' THEN 1 ELSE 0 END) as female_count';
        }
        if ($collectChildren) {
            $selectClause .= ', SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 18 THEN 1 ELSE 0 END) as children_count';
        }
        
        // Single aggregated query for all barangays
        $counts = Patient::whereIn('barangay_id', $barangayIds)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->selectRaw($selectClause)
            ->groupBy('barangay_id')
            ->get()
            ->keyBy('barangay_id');
        
        // Build data arrays matching barangay order
        $maleData = [];
        $femaleData = [];
        $childrenData = [];
        
        foreach ($barangays as $barangay) {
            $count = $counts->get($barangay->id);
            if ($collectMale) {
                $maleData[] = $count ? (int)$count->male_count : 0;
            }
            if ($collectFemale) {
                $femaleData[] = $count ? (int)$count->female_count : 0;
            }
            if ($collectChildren) {
                $childrenData[] = $count ? (int)$count->children_count : 0;
            }
        }
        
        // Build datasets
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
        
        if ($collectChildren) {
            $datasets[] = [
                'label' => 'Children (0-17)',
                'data' => $childrenData,
                'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                'borderColor' => 'rgb(16, 185, 129)',
                'borderWidth' => 1,
            ];
        }
        
        return [
            'datasets' => $datasets,
            'labels' => $barangays->pluck('name')->toArray(),
        ];
    }

    /**
     * Get patient count by barangay with gender breakdown
     * Optimized: Uses single aggregated query instead of 2N queries
     */
    public function getPatientsByBarangayWithGender(int $year, int $month): array
    {
        $barangays = Barangay::orderBy('name')->get();
        
        if ($barangays->isEmpty()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }
        
        $barangayIds = $barangays->pluck('id')->toArray();
        
        // Single aggregated query for all barangays
        $counts = Patient::whereIn('barangay_id', $barangayIds)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->selectRaw('
                barangay_id,
                SUM(CASE WHEN sex = \'male\' THEN 1 ELSE 0 END) as male_count,
                SUM(CASE WHEN sex = \'female\' THEN 1 ELSE 0 END) as female_count
            ')
            ->groupBy('barangay_id')
            ->get()
            ->keyBy('barangay_id');
        
        $maleData = [];
        $femaleData = [];
        
        foreach ($barangays as $barangay) {
            $count = $counts->get($barangay->id);
            $maleData[] = $count ? (int)$count->male_count : 0;
            $femaleData[] = $count ? (int)$count->female_count : 0;
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
     * Optimized: Uses database-level age calculation instead of loading all records
     */
    public function getPatientsByAgeGroup(int $year = null): array
    {
        $year = $year ?? now()->year;

        // Use database-level age calculation instead of loading all records
        $ageGroups = Patient::whereYear('created_at', $year)
            ->selectRaw('
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 18 THEN 1 ELSE 0 END) as age_0_17,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 18 AND 35 THEN 1 ELSE 0 END) as age_18_35,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 36 AND 50 THEN 1 ELSE 0 END) as age_36_50,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 51 AND 65 THEN 1 ELSE 0 END) as age_51_65,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) > 65 THEN 1 ELSE 0 END) as age_65_plus
            ')
            ->first();

        $ageGroupData = [
            '0-17' => (int)($ageGroups->age_0_17 ?? 0),
            '18-35' => (int)($ageGroups->age_18_35 ?? 0),
            '36-50' => (int)($ageGroups->age_36_50 ?? 0),
            '51-65' => (int)($ageGroups->age_51_65 ?? 0),
            '65+' => (int)($ageGroups->age_65_plus ?? 0),
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Patients by Age Group',
                    'data' => array_values($ageGroupData),
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.5)',
                        'rgba(16, 185, 129, 0.5)',
                        'rgba(245, 158, 11, 0.5)',
                        'rgba(239, 68, 68, 0.5)',
                        'rgba(139, 92, 246, 0.5)',
                    ],
                ],
            ],
            'labels' => array_keys($ageGroupData),
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