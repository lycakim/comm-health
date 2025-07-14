<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Enums\RoleEnum;
use App\Models\Patient;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Consultation;
use App\Traits\HasUserTypeUrls;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Filament\Tables\Grouping\Group;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Services\ConsultationFormService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ConsultationResource\Pages;
use App\Filament\Resources\ConsultationResource\RelationManagers;

class ConsultationResource extends Resource
{
    use HasUserTypeUrls;

    protected static ?string $model = Consultation::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    // protected static ?int $navigationSort = 2;

    public static function getNavigationSort(): ?int
    {
        if (self::currentUser()->isMHO()) {
            return 3;
        }
        return 2;
    }

    public static function canAccess(): bool
    {
        return in_array(self::currentUser()->role, [
            RoleEnum::ADMIN,
            RoleEnum::MHO,
            RoleEnum::BHW,
        ]);
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
                Tables\Actions\EditAction::make()
                    ->color('warning'),
                Tables\Actions\Action::make('create_referral')
                    ->label('Create Referral')
                    ->icon('heroicon-o-plus')
                    ->modalHeading(fn ($record) => "Creating a referral for {$record->patient->first_name} {$record->patient->last_name}")
                    ->color('success')
                    ->requiresConfirmation(false)
                    ->modalWidth('md')
                    ->slideOver()
                    ->form([
                        TextInput::make('referral_reason')
                            ->label('Referral Reason')
                            ->required()
                            ->maxLength(255),
                        
                        Textarea::make('notes')
                            ->label('Additional Notes')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            Notification::make()
                                ->title('Referral created successfully')
                                ->success()
                                ->send();
                            
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error creating referral')
                                ->danger()
                                ->send();
                        }
                    })
                    ->after(function () {
                        // Explicitly prevent any table refresh
                        // This ensures the table state remains intact
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->groups([
                'date',
                'patient.first_name',
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

    // initialize auth user
    public static function currentUser(): ?User
    {
        return Auth::user();
    }
}