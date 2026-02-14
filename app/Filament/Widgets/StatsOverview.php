<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Patient;
use App\Models\Program;
use App\Models\Referral;
use App\Models\Consultation;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use App\Filament\Resources\PatientResource;
use App\Filament\Resources\ConsultationResource;
use App\Filament\Resources\ReferralResource;
use App\Filament\Resources\ProgramResource;
use App\Filament\Resources\UserResource;
use App\Filament\Pages\Reports;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        
        // Get the user's assigned barangay_id (for BHWs/Midwives)
        $barangayId = null;
        if ($user->isBHW() || $user->isMidwife()) {
            $barangayId = $user->barangay_id;
        }
        
        // Base queries with barangay filtering for BHWs/Midwives
        $patientQuery = Patient::query();
        $consultationQuery = Consultation::query();
        $referralQuery = Referral::query();
        $programQuery = Program::query();
        
        // For BHW/Midwife: filter by barangay_id, if null show empty (0 counts)
        if ($user->isBHW() || $user->isMidwife()) {
            if ($barangayId) {
                // Filter by assigned barangay
                $patientQuery->where('barangay_id', $barangayId);
                $consultationQuery->whereHas('patient', function ($query) use ($barangayId) {
                    $query->where('barangay_id', $barangayId);
                });
                $referralQuery->whereHas('patient', function ($query) use ($barangayId) {
                    $query->where('barangay_id', $barangayId);
                });
                $programQuery->where('barangay_id', $barangayId);
            } else {
                // No barangay assigned - return empty queries (0 counts)
                $patientQuery->whereRaw('1 = 0');
                $consultationQuery->whereRaw('1 = 0');
                $referralQuery->whereRaw('1 = 0');
                $programQuery->whereRaw('1 = 0');
            }
        }
        
        // Calculate percentage changes from last month
        $patientIncrease = $this->calculatePercentageChange(Patient::class, null, $barangayId);
        $consultationIncrease = $this->calculatePercentageChange(Consultation::class, null, $barangayId);
        $referralChange = $this->calculatePercentageChange(Referral::class, 'pending', $barangayId);
        
        // Get upcoming program
        $nextProgram = $programQuery->where('created_at', '>', now())
            ->orderBy('created_at')
            ->first();
            
        // Count barangays with health workers (only for Admin/MHO)
        $barangaysWithHealthWorkers = 0;
        if ($user->isAdmin() || $user->isMHO()) {
            $barangaysWithHealthWorkers = User::where('role', 'bhw')
                ->whereNotNull('barangay_id')
                ->distinct('id')
                ->count();
        }

        // Get total counts
        $totalPatients = $patientQuery->count();
        $totalConsultations = $consultationQuery->count();
        $pendingReferrals = (clone $referralQuery)->where('status', 'pending')->count();
        $upcomingProgramsCount = (clone $programQuery)->where('created_at', '>', now())->count();

        if ($user->isAdmin() || $user->isMHO()) {
            return [
                Stat::make('Total Residents', $totalPatients)
                    ->description('+' . $patientIncrease . '% from last month')
                    ->descriptionIcon($patientIncrease > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->chart([10, 12, 13, 14, 15, 12]) // Replace with actual data points
                    ->color('success')
                    ->url(PatientResource::getUrl('index')),
                    
                Stat::make('Consultations', $totalConsultations)
                    ->description('+' . $consultationIncrease . '% from last month')
                    ->descriptionIcon($consultationIncrease > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->chart([5, 8, 12, 7, 9, 10]) // Replace with actual data points
                    ->color('success')
                    ->url(ConsultationResource::getUrl('index')),
                    
                Stat::make('Pending Referrals', $pendingReferrals)
                    ->description($referralChange > 0 ? '+' : '' . $referralChange . '% from last month')
                    ->descriptionIcon($referralChange > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->chart([4, 3, 5, 6, 4, 3]) // Replace with actual data points
                    ->color($referralChange > 0 ? 'danger' : 'success')
                    ->url(ReferralResource::getUrl('index')),
                    
                Stat::make('Upcoming Programs', $upcomingProgramsCount)
                    ->description('Next: ' . ($nextProgram ? $nextProgram->name . ' (' . $nextProgram->date->format('M d') . ')' : 'None'))
                    ->color('primary')
                    ->url(ProgramResource::getUrl('index')),
                    
                Stat::make('Reports Submitted', 12)
                    ->description('+' . 10 . ' this week')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('success')
                    ->url(Reports::getUrl()),
                    
                Stat::make('Active Health Workers', $barangaysWithHealthWorkers)
                    ->description('Across ' . $barangaysWithHealthWorkers . ' barangays')
                    ->color('primary')
                    ->url(UserResource::getUrl('index')),
            ];
        }
        
        if ($user->isResident()) {
            return [];
        }
        
        // BHW/Midwife stats - filtered by assigned barangay
        return [
            Stat::make('Registered Residents', $totalPatients)
                ->description($barangayId ? '+' . $patientIncrease . '% from last month' : 'No barangay assigned')
                ->descriptionIcon($patientIncrease > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart([10, 12, 13, 14, 15, 12]) // Replace with actual data points
                ->color('success')
                ->url(PatientResource::getUrl('index')),
                
            Stat::make('Consultations', $totalConsultations)
                ->description($barangayId ? '+' . $consultationIncrease . '% from last month' : 'No barangay assigned')
                ->descriptionIcon($consultationIncrease > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart([5, 8, 12, 7, 9, 10]) // Replace with actual data points
                ->color('success')
                ->url(ConsultationResource::getUrl('index')),
                
            Stat::make('Pending Referrals', $pendingReferrals)
                ->description($barangayId ? ($referralChange > 0 ? '+' : '') . $referralChange . '% from last month' : 'No barangay assigned')
                ->descriptionIcon($referralChange > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart([4, 3, 5, 6, 4, 3]) // Replace with actual data points
                ->color($referralChange > 0 ? 'danger' : 'success')
                ->url(ReferralResource::getUrl('index')),
                
            Stat::make('Upcoming Programs', $upcomingProgramsCount)
                ->description('Next: ' . ($nextProgram ? $nextProgram->name . ' (' . $nextProgram->date->format('M d') . ')' : 'None'))
                ->color('primary')
                ->url(ProgramResource::getUrl('index')),
        ];
    }

    private function calculatePercentageChange(string $model, ?string $condition = null, ?int $barangayId = null): int
    {
        $user = Auth::user();
        
        // For BHW/Midwife without barangay_id, return 0 (no data to compare)
        if (($user->isBHW() || $user->isMidwife()) && is_null($barangayId)) {
            return 0;
        }
        
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
        
        // Apply barangay filtering if barangay_id is provided
        if ($barangayId) {
            if ($model === Patient::class) {
                $queryLastMonth->where('barangay_id', $barangayId);
                $queryCurrentMonth->where('barangay_id', $barangayId);
            } elseif ($model === Consultation::class) {
                $queryLastMonth->whereHas('patient', function ($query) use ($barangayId) {
                    $query->where('barangay_id', $barangayId);
                });
                $queryCurrentMonth->whereHas('patient', function ($query) use ($barangayId) {
                    $query->where('barangay_id', $barangayId);
                });
            } elseif ($model === Referral::class) {
                $queryLastMonth->whereHas('patient', function ($query) use ($barangayId) {
                    $query->where('barangay_id', $barangayId);
                });
                $queryCurrentMonth->whereHas('patient', function ($query) use ($barangayId) {
                    $query->where('barangay_id', $barangayId);
                });
            }
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