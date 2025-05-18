<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Models\Referral;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentReferrals extends BaseWidget
{
    protected static ?string $heading = 'Recent Referrals';

    protected static ?string $subheading = 'Latest patient referrals processed';

    protected static ?int $sort = 4;

    // protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->description("Latest patient referrals requiring attention")
            ->query(
                Referral::with(['consultation.patient.barangay'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('consultation.patient.first_name')
                    ->label('Patient')
                    ->formatStateUsing(fn ($state, $record) => 
                        $record->consultation && $record->consultation->patient 
                            ? "{$record->consultation->patient->first_name} {$record->consultation->patient->last_name}" 
                            : '-')
                    // ->searchable(query: function (Builder $query, string $search): Builder {
                    //     return $query->whereHas('consultation.patient', function (Builder $query) use ($search) {
                    //         $query->where('first_name', 'like', "%{$search}%")
                    //               ->orWhere('last_name', 'like', "%{$search}%");
                    //     });
                    // })
                    ->description(function ($record) {
                        if (!$record->consultation || !$record->consultation->patient) {
                            return null;
                        }
                        
                        $patient = $record->consultation->patient;
                        $age = $patient->birth_date 
                            ? Carbon::parse($patient->birth_date)->age . ' y/o'
                            : '';
                            
                        $barangay = $patient->barangay && $patient->barangay->name
                            ? $patient->barangay->name
                            : '';
                            
                        if ($age && $barangay) {
                            return "$age • $barangay";
                        }
                        
                        return $age ?: $barangay;
                    })
                    ->sortable(),
                // TextColumn::make('consultation.patient.birth_date')
                //     ->label('Age')
                //     ->formatStateUsing(function ($state, $record) {
                //         return $record->consultation && $record->consultation->patient && $record->consultation->patient->birth_date 
                //             ? Carbon::parse($record->consultation->patient->birth_date)->age 
                //             : '-';
                //     }),
                // TextColumn::make('consultation.patient.barangay.name')
                //     ->label('Location'),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->date('M d, Y'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->paginated(false);
    }

    protected function getType(): string
    {
        return 'list';
    }
}