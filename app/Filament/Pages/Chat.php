<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Concerns\HasDashboardBreadcrumb;

class Chat extends Page
{
    use HasDashboardBreadcrumb;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static string $view = 'filament.pages.chat';
    
    protected static ?string $navigationLabel = 'Messages';
    
    protected static ?string $title = 'Chat Messages';
    
    protected static ?int $navigationSort = 8;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}