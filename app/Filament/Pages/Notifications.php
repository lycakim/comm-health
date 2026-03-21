<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use App\Filament\Widgets\PatientChart;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\Concerns\HasDashboardBreadcrumb;

class Notifications extends Page
{
    use HasDashboardBreadcrumb;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static string $view = 'filament.pages.notifications';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return Auth::user()->isResident();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'View your notifications';
    }

    protected function getFooterWidgets(): array
    {
        return [
            PatientChart::class,
            PatientChart::class,
        ];
    }
}