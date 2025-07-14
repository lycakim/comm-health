<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Enums\RoleEnum;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use App\Filament\Widgets\PatientChart;
use Illuminate\Contracts\Support\Htmlable;

class Analytics extends Page
{    
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.analytics';

    protected static ?string $navigationGroup = 'Data  & Reports';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return in_array(self::currentUser()->role, [
            RoleEnum::ADMIN,
            RoleEnum::MHO,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fiscal_year')
                ->label('Select Fiscal Year')
                ->color('gray')
                ->icon('heroicon-o-calendar')
                ->form([
                    Select::make('fiscal_year')
                        ->options([
                            '2024' => '2024',
                            '2023' => '2023',
                            '2022' => '2022',
                        ])
                        ->default(date('Y'))
                ]),
            Action::make('export')
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->disabled(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            PatientChart::class,
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'View analytics reports';
    }

    // initialize auth user
    public static function currentUser(): ?User
    {
        return Auth::user();
    }
}