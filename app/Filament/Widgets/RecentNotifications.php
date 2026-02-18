<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Announcement;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use App\Enums\RoleEnum;
use App\Filament\Resources\AnnouncementResource;

class RecentNotifications extends BaseWidget
{
    protected static ?string $heading = 'Recent Announcements';

    protected static ?string $description = 'Latest updates and announcements from MHO';

    protected static ?int $sort = 4;

    public function getColumnSpan(): int | string | array
    {
        $user = Auth::user();
        // Make columnSpanFull when user is NOT MHO
        if ($user && !$user->isMHO()) {
            return 'full';
        }
        
        return parent::getColumnSpan();
    }

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Tables\Actions\Action::make('viewAll')
                    ->label('View All')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(function () {
                        $role = Auth::user()->role;
                        return in_array($role, [RoleEnum::ADMIN, RoleEnum::MHO])
                            ? '/commhealth/announcements'
                            : '/commhealth/notifications';
                    })
                    ->visible(fn () => in_array(Auth::user()->role, [RoleEnum::ADMIN, RoleEnum::MHO]))
            ])
            ->query(
                Announcement::latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Grid::make()
                    ->schema([
                        TextColumn::make('title')
                            ->weight('bold')
                            ->label('Program')
                            ->description(function ($record) {
                                return $record->content ? $record->content : '-';
                            }),
                        TextColumn::make('program_date')
                            ->label('Date')
                            ->date()
                            ->formatStateUsing(fn ($state) => $state ? $state->format('M d, Y') : '')
                            ->badge()
                            ->alignEnd(),
                    ]),
                ]),
            ])
            ->contentGrid([
                'md' => 1,
                'xl' => 1,
            ])
            ->paginated(false);
    }

    protected function getType(): string
    {
        return 'list';
    }
}