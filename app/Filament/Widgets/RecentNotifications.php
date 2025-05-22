<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Announcement;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentNotifications extends BaseWidget
{
    protected static ?string $heading = 'Recent Notifications';

    protected static ?string $description = 'Latest updates and announcements from MHO';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Announcement::latest()
                    ->limit(10)
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