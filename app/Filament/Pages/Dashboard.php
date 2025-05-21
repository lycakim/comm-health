<?php

namespace App\Filament\Pages;

use App\Models\Barangay;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\RecentPatients;
use App\Filament\Widgets\RecentReferrals;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\Widgets\HealthServicesChart;
use App\Filament\Widgets\RecentNotifications;
use App\Filament\Widgets\PatientCategoryChart;
use App\Filament\Widgets\UpcomingHealthPrograms;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    // protected static ?string $title = 'MHO Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 1;

    public $selectedBarangay = null;

    public $selectedYear = null;

    // public function getSubheading(): string|Htmlable|null
    // {
    //     return 'Overview of health services and activities in Carmen, Davao del Norte';
    // }

    public function mount(): void
    {
        $this->selectedYear = now()->year;
    }
    
    protected function getHeaderWidgets(): array
    {
        $userRole = auth()->user()->role;
        
        $widgets = [
            StatsOverview::class,
        ];
        
        return $widgets;
    }
    
    protected function getFooterWidgets(): array
    {
        $userRole = auth()->user()->role;
        $widgets = [];
        
        switch ($userRole) {
            case 'admin':
                $widgets = [
                    HealthServicesChart::class,
                    PatientCategoryChart::class,
                    RecentReferrals::class,
                    RecentPatients::class,
                    UpcomingHealthPrograms::class,
                    RecentNotifications::class,
                ];
                break;
                
            case 'mho':
                $widgets = [
                    HealthServicesChart::class,
                    PatientCategoryChart::class,
                    RecentReferrals::class,
                    RecentPatients::class,
                    UpcomingHealthPrograms::class,
                    RecentNotifications::class,
                ];
                break;
                
            case 'midwife':
                $widgets = [
                    PatientCategoryChart::class,
                    RecentPatients::class,
                    UpcomingHealthPrograms::class,
                    RecentNotifications::class,
                ];
                break;
                
            case 'bhw':
                $widgets = [
                    RecentPatients::class,
                    UpcomingHealthPrograms::class,
                    RecentNotifications::class,
                ];
                break;
                
            case 'resident':
            case 'patient':
                $widgets = [
                    UpcomingHealthPrograms::class,
                    RecentNotifications::class,
                ];
                break;
                
            default:
                $widgets = [
                    RecentNotifications::class,
                ];
                break;
        }
        
        return $widgets;
    }
    
    protected function getHeaderActions(): array
    {
        $userRole = auth()->user()->role;
        
        // Only certain roles can use filters
        if (!in_array($userRole, ['superadmin', 'mho', 'midwife'])) {
            return [];
        }
        
        $actions = [];
        
        // Barangay filter - available for superadmin, mho, and midwife
        if (in_array($userRole, ['superadmin', 'mho', 'midwife'])) {
            $actions[] = \Filament\Actions\Action::make('filterBarangay')
                ->label('Select Barangay')
                ->form([
                    Select::make('barangay')
                        ->label('Barangay')
                        ->options(Barangay::pluck('name', 'id'))
                        ->placeholder('All Barangays')
                        ->live(),
                ])
                ->action(function (array $data): void {
                    $this->selectedBarangay = $data['barangay'] ?? null;
                });
        }
        
        // Year filter - available for superadmin and mho
        if (in_array($userRole, ['admin', 'mho'])) {
            $actions[] = \Filament\Actions\Action::make('filterYear')
                ->label('Select Year')
                ->form([
                    Select::make('year')
                        ->label('Year')
                        ->options([
                            now()->year => now()->year,
                            now()->year - 1 => now()->year - 1,
                            now()->year - 2 => now()->year - 2,
                        ])
                        ->default(now()->year)
                        ->live(),
                ])
                ->action(function (array $data): void {
                    $this->selectedYear = $data['year'];
                });
        }
        
        // View Overall button - available for superadmin and mho
        if (in_array($userRole, ['superadmin', 'mho'])) {
            $actions[] = \Filament\Actions\Action::make('viewOverall')
                ->label('View Overall')
                ->button()
                ->color('success')
                ->action(function (): void {
                    $this->selectedBarangay = null;
                    $this->selectedYear = now()->year;
                });
        }
        
        return $actions;
    }

    public function getTitle(): string|Htmlable
    {
        $userRole = auth()->user()->role;
        
        return match($userRole) {
            'admin' => 'Admin Dashboard',
            'mho' => 'MHO Dashboard',
            'midwife' => 'Midwife Dashboard',
            'bhw' => 'Barangay Health Worker Dashboard',
            'resident', 'patient' => 'Patient Dashboard',
            default => 'Dashboard'
        };
    }

    public function getSubheading(): string|Htmlable|null
    {
        $userRole = auth()->user()->role;
        
        return match($userRole) {
            'admin' => 'Complete system overview and administration',
            'mho' => 'Overview of health services and activities in Carmen, Davao del Norte',
            'midwife' => 'Patient care and health program overview',
            'bhw' => 'Community health information for your barangay',
            'resident', 'patient' => 'Your health information and available services',
            default => 'Health services overview'
        };
    }
}