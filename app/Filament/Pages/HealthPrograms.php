<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Enums\RoleEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class HealthPrograms extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static string $view = 'filament.pages.health-programs';

    protected static ?string $navigationGroup = 'Programs';

    public string $activeTab = 'calendar';

    public static function canAccess(): bool
    {
        return in_array(self::currentUser()->role, [
            RoleEnum::BHW,
            RoleEnum::MIDWIFE,
            RoleEnum::ADMIN,
        ]);
    }

    public static function currentUser(): ?User
    {
        return Auth::user();
    }
}