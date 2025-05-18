<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Patient;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Consultation;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\RichEditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ConsultationResource\Pages;
use App\Filament\Resources\ConsultationResource\RelationManagers;

class ConsultationResource extends Resource
{
    protected static ?string $model = Consultation::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Patient Information')
                    ->schema([
                        Select::make('patient_id')
                            ->label('Patient')
                            ->options(function () {
                                return Patient::query()
                                    ->get()
                                    ->mapWithKeys(function ($patient) {
                                        return [$patient->id => $patient->first_name . ' ' . $patient->last_name];
                                    })
                                    ->toArray();
                            })
                            ->required(),
                        Forms\Components\DateTimePicker::make('date')
                            ->default(Carbon::now())
                            ->required(),
                    ])->columns(2),

                Section::make('Consultation Details')
                    ->schema([
                        Forms\Components\Textarea::make('chief_complaint')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('diagnosis')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('treatment')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('follow_up_date'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'completed' => 'Completed',
                                'needs_referral' => 'Needs Referral',
                                'referred' => 'Referred',
                            ])
                            ->default('completed')
                            ->required(),
                    ]),

                Section::make('Referral')
                    ->description('If this patient needs to be referred, mark status as "Needs Referral" or "Referred" and save first, then create a referral.')
                    ->schema([
                        Forms\Components\Placeholder::make('referral_instructions')
                            ->content('After saving this consultation, you can create a referral from the consultation details page.'),
                    ])
                    ->visible(fn (Forms\Get $get) => in_array($get('status'), ['needs_referral', 'referred'])),

                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id())
                    ->required(),
            ]);
    }

    // public static function form(Form $form): Form
    // {
    //     return $form
    //         ->schema([
    //             Section::make()
    //                 ->schema([
    //                     Select::make('patient_id')
    //                         ->label('Patient')
    //                         ->options(function () {
    //                             return Patient::query()
    //                                 ->get()
    //                                 ->mapWithKeys(function ($patient) {
    //                                     return [$patient->id => $patient->first_name . ' ' . $patient->last_name];
    //                                 })
    //                                 ->toArray();
    //                         })
    //                         ->required(),
    //                     Select::make('consultation_type')
    //                         ->options([
    //                             'Prenatal' => 'Prenatal',
    //                             'Postnatal' => 'Postnatal',
    //                             'Immunization' => 'Immunization',
    //                             'Chronic Disease' => 'Chronic Disease',
    //                         ])
    //                         ->required(),
    //                     Select::make('consultation_program')
    //                         ->options([
    //                             'Prenatal' => 'Prenatal',
    //                             'Postnatal' => 'Postnatal',
    //                             'Immunization' => 'Immunization',
    //                             'Chronic Disease' => 'Chronic Disease',
    //                         ])
    //                         ->required(),
    //                     RichEditor::make('notes')
    //                         ->toolbarButtons([
    //                             'attachFiles',
    //                             'blockquote',
    //                             'bold',
    //                             'bulletList',
    //                             'codeBlock',
    //                             'h2',
    //                             'h3',
    //                             'italic',
    //                             'link',
    //                             'orderedList',
    //                             'redo',
    //                             'strike',
    //                             'underline',
    //                             'undo',
    //                         ])
    //                 ])
    //                 ->columnSpan(2),
    //             Section::make()
    //                 ->schema([
    //                     ViewField::make('rating')
    //                         ->dehydrated(false)
    //                         ->view('filament.forms.components.consultation-stats')
    //                         ->viewData([
    //                             'totalPatients' => 12,
    //                             'maternalCount' => 5,
    //                             'childCount' => 5,
    //                             'seniorCount' => 4,
    //                             'chronicCount' => 1,
    //                             'recentActivities' => 0,
    //                         ])
    //                 ])
    //                 ->columnSpan(1),
    //         ])
    //         ->columns(3);
    // }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient_full_name')
                        ->label('Patient Name')
                        ->getStateUsing(fn ($record) => $record->patient->last_name . ', ' . $record->patient->first_name)
                        ->searchable(['patient.first_name', 'patient.last_name'])
                        ->sortable(query: function ($query, $direction) {
                            return $query->orderBy('patient.last_name', $direction)
                                        ->orderBy('patient.first_name', $direction);
                        }),
                Tables\Columns\TextColumn::make('date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('chief_complaint')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'needs_referral' => 'warning',
                        'referred' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
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
            'index' => Pages\ListConsultations::route('/'),
            'create' => Pages\CreateConsultation::route('/create'),
            'edit' => Pages\EditConsultation::route('/{record}/edit'),
        ];
    }
}