<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class Settings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static string $view = 'filament.pages.settings';

    protected static ?string $navigationGroup = 'System Settings';

    protected static ?int $navigationSort = 4;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Manage your account settings';
    }
}