<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Tables;
use App\Models\Program;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Widgets\TableWidget as BaseWidget;

class PastProgramWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Program::query()
                    ->whereDate('program_start_date', '<', now()->startOfDay())
                    ->whereDate('program_start_date', '>=', now()->subDays(30)->startOfDay())
                    ->when(
                        !Auth::user()->role === 'mho',
                        function ($query) {
                            $user = Auth::user();
                            $barangay = $user->barangays->first();
                            if ($barangay) {
                                $query->where('barangay_id', $barangay->id);
                            } else {
                                // Optionally, return no records if no assigned barangay
                                $query->whereRaw('1 = 0');
                            }
                        }
                    )
                    ->orderByDesc('program_start_date')
            )
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Grid::make()
                        ->schema([
                            TextColumn::make('name')
                                ->weight('bold')
                                ->size('lg'),
                            TextColumn::make('program_start_date')
                                ->label('Program Date')
                                ->icon('heroicon-o-calendar')
                                ->weight('bold')
                                ->date('M d, Y')
                                ->formatStateUsing(function ($record) {
                                    $start = $record->program_start_date
                                        ? $record->program_start_date->format('M d, Y')
                                        : null;

                                    $end = $record->program_end_date
                                        ? $record->program_end_date->format('M d, Y')
                                        : null;

                                    return $end
                                        ? "{$start} - {$end}"
                                        : $start;
                                }),
                            TextColumn::make('program_start_time')
                                ->label('Program Time')
                                ->icon('heroicon-o-clock')
                                ->weight('bold')
                                ->formatStateUsing(function ($record) {
                                    $start = $record->program_start_time
                                        ? \Carbon\Carbon::parse($record->program_start_time)->format('H:i A')
                                        : null;

                                    $end = $record->program_end_time
                                        ? \Carbon\Carbon::parse($record->program_end_time)->format('H:i A')
                                        : null;

                                    return $end
                                        ? "{$start} - {$end}"
                                        : $start;
                                }),
                            TextColumn::make('category.name')
                                ->badge()
                                ->colors([
                                    'success' => 'Immunization',
                                    'info' => 'Maternal',
                                    'secondary' => 'Dental',
                                    'danger' => 'Senior',
                                    'warning' => 'Family Planning',
                                ]),
                                // ->alignEnd(),
                            TextColumn::make('barangay.name')
                                ->weight('bold')
                                ->icon('heroicon-o-map-pin')
                                ->label('Assigned Barangay'),
                            TextColumn::make('description')
                                ->columnSpanFull()
                                ->placeholder('No description available.')
                                ->html()
                        ]),
                ]),
            ])
            ->heading('')
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