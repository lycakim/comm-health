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

class UpcomingHealthPrograms extends BaseWidget
{
    protected static ?string $heading = 'Upcoming Health Programs';

    protected static ?string $description = 'Scheduled health programs for the next 30 days';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->description("Scheduled health programs for the next 30 days")
            ->query(
                // Program::query()
                //     ->whereDate('program_date', '>=', now())
                //     ->whereDate('program_date', '<=', now()->addDays(30))
                //     ->orderBy('program_date')
                Program::latest()
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
                    ->limit(5)
            )
            ->headerActions([
                Tables\Actions\Action::make('view')
                    ->color('gray')
                    ->outlined()
                    ->label('View All Programs')
                    // ->url(fn (Program) => route('programs.view', $record))
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Grid::make()
                    ->schema([
                        TextColumn::make('name')
                            ->weight('bold')
                            ->label('Program')
                            ->description(function ($record) {
                                $formattedDate = $record->program_start_date ? Carbon::parse($record->program_start_date)->format('M d, Y') : '';

                                $location = $record->barangay && $record->barangay->name
                                    ? $record->barangay->name
                                    : '';

                                if ($formattedDate && $location) {
                                    return "$formattedDate at $location";
                                }
                                
                                return $formattedDate ?: $location;
                            }),
                        TextColumn::make('category.name')
                            ->badge()
                            ->colors([
                                'success' => 'Immunization',
                                'info' => 'Maternal',
                                'secondary' => 'Dental',
                                'danger' => 'Senior',
                                'warning' => 'Family Planning',
                            ])
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