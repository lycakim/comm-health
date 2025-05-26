<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Consultation;
use Filament\Resources\Resource;
use App\Services\ConsultationFormService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ConsultationResource\Pages;
use App\Filament\Resources\ConsultationResource\RelationManagers;

class ConsultationResource extends Resource
{
    protected static ?string $model = Consultation::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    // protected static ?int $navigationSort = 2;

    public static function getNavigationSort(): ?int
    {
        if (auth()->user()->isMHO()) {
            return 3;
        }
        return 2;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isMHO() || auth()->user()->isBHW();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(ConsultationFormService::handle());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient_full_name')
                    ->label('Patient Name')
                    ->getStateUsing(fn ($record) => $record->patient->first_name . ' ' . $record->patient->last_name)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->with('patient')
                            ->where('id', 'like', "%{$search}%")
                            ->orWhereHas('patient', function (Builder $patientQuery) use ($search) {
                                $patientQuery->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%");
                            });
                    }),
                Tables\Columns\TextColumn::make('date')
                    ->dateTime()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->label('Created By')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'completed' => 'Completed',
                        'needs_referral' => 'Needs Referral',
                        'referred' => 'Referred',
                    ]),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('create_referral')
                    ->label('Create Referral')
                    ->url(fn (Consultation $record) => ReferralResource::getUrl('create', ['consultation_id' => $record->id]))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn (Consultation $record) => in_array($record->status, ['needs_referral', 'referred']) && !$record->referral),
                Tables\Actions\Action::make('view_referral')
                    ->label('View Referral')
                    ->url(fn (Consultation $record) => $record->referral ? ReferralResource::getUrl('view', ['record' => $record->referral]) : null)
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (Consultation $record) => $record->referral),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\IndexConsultations::route('/'),
            'all' => Pages\AllConsultations::route('/all'),
            'list' => Pages\ListConsultations::route('/list/{barangay?}'),
            'create' => Pages\CreateConsultation::route('/create'),
            'edit' => Pages\EditConsultation::route('/{record}/edit'),
        ];
    }
}