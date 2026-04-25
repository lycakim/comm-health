<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Tables;
use App\Enums\RoleEnum;
use App\Models\Program;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingHealthPrograms extends BaseWidget
{
    protected static ?string $heading = 'Upcoming Health Programs';

    protected static ?string $description = 'Scheduled health programs that have not yet started';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->description("Scheduled health programs that have not yet started")
            ->query(
                // Program::query()
                //     ->whereDate('program_date', '>=', now())
                //     ->whereDate('program_date', '<=', now()->addDays(30))
                //     ->orderBy('program_date')
                Program::whereDate('program_start_date', '>', now())
                    ->orderBy('program_start_date')
                    ->when(
                        !Auth::user()->isAdmin() && !Auth::user()->isMHO(),
                        function ($query) {
                            $barangayId = Auth::user()->barangay_id;

                            if ($barangayId) {
                                $query->where('barangay_id', $barangayId);
                            } else {
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
                    ->url(function () {
                        $role = Auth::user()->role;
                        return in_array($role, [RoleEnum::ADMIN, RoleEnum::MHO])
                            ? '/commhealth/programs'
                            : '/commhealth/health-programs';
                    })
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