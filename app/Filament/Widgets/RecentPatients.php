<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Tables;
use App\Enums\RoleEnum;
use App\Models\Patient;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentPatients extends BaseWidget
{
    protected static ?string $heading = 'Recent Patients';

    protected static ?string $subheading = 'Latest patients added to the system';
    
    public function table(Table $table): Table
    {
        return $table
            ->description("Patients with recent consultations or upcoming appointments")
            ->query(
                Patient::latest()
                    ->when(
                        Auth::user()->role !== RoleEnum::MHO->value,
                        function ($query) {
                            $barangayId = Auth::user()->barangays()->first()->id;
                            
                            if ($barangayId) {
                                $query->where('barangay_id', $barangayId);
                            } else {
                                $query->whereRaw('1 = 0');
                            }
                        }
                    )
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->label('Patient')
                    ->formatStateUsing(fn ($state, $record) => 
                        $record->first_name . ' ' . $record->last_name
                            ? "{$record->first_name} {$record->last_name}" 
                            : '-')
                    ->description(function ($record) {
                        if (!$record->birth_date || !$record->barangay) {
                            return null;
                        }

                        $age = $record->birth_date 
                            ? Carbon::parse($record->birth_date)->age . ' y/o'
                            : '';
                            
                        $barangay = $record->barangay && $record->barangay->name
                            ? $record->barangay->name
                            : '';
                            
                        if ($age && $barangay) {
                            return "$age • $barangay";
                        }
                        
                        return $age ?: $barangay;
                    }),
                Tables\Columns\TextColumn::make('category.name')
                    ->label(false)
                    ->badge()
                    ->colors([
                        'success' => 'Immunization',
                        'info' => 'Maternal',
                        'secondary' => 'Dental',
                        'danger' => 'Senior',
                        'warning' => 'Family Planning',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->color('gray')
                    ->outlined()
                    ->label('View Patient')
            ])
            ->paginated(false);
    }

    protected function getType(): string
    {
        return 'list';
    }
}