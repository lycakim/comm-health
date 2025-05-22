<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Patient;
use App\Models\Program;
use App\Models\Referral;
use App\Models\Consultation;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Calculate percentage changes from last month
        $patientIncrease = $this->calculatePercentageChange(Patient::class);
        $consultationIncrease = $this->calculatePercentageChange(Consultation::class);
        $referralChange = $this->calculatePercentageChange(Referral::class, 'pending');
        // $reportIncrease = $this->calculatePercentageThisWeek(Report::class);
        
        // Get upcoming program
        $nextProgram = Program::where('created_at', '>', now())
            ->orderBy('created_at')
            ->first();
            
        // Count barangays with health workers
        $barangaysWithHealthWorkers = User::where('role', 'bhw')
            ->count();

        if (auth()->user()->isAdmin() || auth()->user()->isMHO()) {
            return [
                Stat::make('Total Patients', Patient::count())
                    ->description('+' . $patientIncrease . '% from last month')
                    ->descriptionIcon($patientIncrease > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->chart([10, 12, 13, 14, 15, 12]) // Replace with actual data points
                    ->color('success'),
                    
                Stat::make('Consultations', Consultation::count())
                    ->description('+' . $consultationIncrease . '% from last month')
                    ->descriptionIcon($consultationIncrease > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->chart([5, 8, 12, 7, 9, 10]) // Replace with actual data points
                    ->color('success'),
                    
                Stat::make('Pending Referrals', Referral::where('status', 'pending')->count())
                    ->description($referralChange > 0 ? '+' : '' . $referralChange . '% from last month')
                    ->descriptionIcon($referralChange > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->chart([4, 3, 5, 6, 4, 3]) // Replace with actual data points
                    ->color($referralChange > 0 ? 'danger' : 'success'), // Negative trend is good for pending referrals
                    
                Stat::make('Upcoming Programs', Program::where('created_at', '>', now())->count())
                    ->description('Next: ' . ($nextProgram ? $nextProgram->name . ' (' . $nextProgram->date->format('M d') . ')' : 'None'))
                    ->color('primary'),
                    
                Stat::make('Reports Submitted', 12)
                    ->description('+' . 10 . ' this week')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('success'),
                    
                Stat::make('Active Health Workers', $barangaysWithHealthWorkers)
                    ->description('Across ' . $barangaysWithHealthWorkers . ' barangays')
                    ->color('primary'),
            ];
        }
        return [
                Stat::make('Registered Patients', Patient::count())
                    ->description('+' . $patientIncrease . '% from last month')
                    ->descriptionIcon($patientIncrease > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->chart([10, 12, 13, 14, 15, 12]) // Replace with actual data points
                    ->color('success'),
                    
                Stat::make('Consultations', Consultation::count())
                    ->description('+' . $consultationIncrease . '% from last month')
                    ->descriptionIcon($consultationIncrease > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->chart([5, 8, 12, 7, 9, 10]) // Replace with actual data points
                    ->color('success'),
                    
                Stat::make('Pending Referrals', Referral::where('status', 'pending')->count())
                    ->description($referralChange > 0 ? '+' : '' . $referralChange . '% from last month')
                    ->descriptionIcon($referralChange > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->chart([4, 3, 5, 6, 4, 3]) // Replace with actual data points
                    ->color($referralChange > 0 ? 'danger' : 'success'), // Negative trend is good for pending referrals
                    
                Stat::make('Upcoming Programs', Program::where('created_at', '>', now())->count())
                    ->description('Next: ' . ($nextProgram ? $nextProgram->name . ' (' . $nextProgram->date->format('M d') . ')' : 'None'))
                    ->color('primary'),
            ];
    }

    private function calculatePercentageChange(string $model, ?string $condition = null): int
    {
        $lastMonth = now()->subMonth();
        $currentMonth = now();
        
        $queryLastMonth = $model::whereBetween('created_at', [
            $lastMonth->startOfMonth(),
            $lastMonth->endOfMonth(),
        ]);
        
        $queryCurrentMonth = $model::whereBetween('created_at', [
            $currentMonth->startOfMonth(),
            $currentMonth->endOfMonth(),
        ]);
        
        if ($condition) {
            $queryLastMonth->where('status', $condition);
            $queryCurrentMonth->where('status', $condition);
        }
        
        $lastMonthCount = $queryLastMonth->count();
        $currentMonthCount = $queryCurrentMonth->count();
        
        if ($lastMonthCount === 0) return 100;
        
        return round((($currentMonthCount - $lastMonthCount) / $lastMonthCount) * 100);
    }
    
    private function calculatePercentageThisWeek(string $model): int
    {
        $lastWeek = now()->subWeek();
        $thisWeek = now();
        
        $lastWeekCount = $model::whereBetween('created_at', [
            $lastWeek->startOfWeek(),
            $lastWeek->endOfWeek(),
        ])->count();
        
        $thisWeekCount = $model::whereBetween('created_at', [
            $thisWeek->startOfWeek(),
            $thisWeek,
        ])->count();
        
        return $thisWeekCount;
    }
}