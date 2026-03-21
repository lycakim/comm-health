<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use App\Filament\Widgets\PatientChart;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Filament\Concerns\HasDashboardBreadcrumb;

class HealthRecords extends Page implements HasForms
{
    use HasDashboardBreadcrumb;
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static string $view = 'filament.pages.health-records';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return Auth::user()->isResident();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'View your personal health information and medical history';
    }

    protected function getFooterWidgets(): array
    {
        return [
            PatientChart::class,
            PatientChart::class,
        ];
    }
}