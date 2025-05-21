<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Enums\SexEnum;
use App\Models\Patient;
use Filament\Forms\Get;
use App\Models\Category;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Consultation;
use App\Enums\CivilStatusEnum;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use App\Enums\EducationalAttainmentEnum;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\RichEditor;
use Illuminate\Database\Eloquent\Builder;
use App\Services\ConsultationFormOptionServices;
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Patient Information')
                    ->schema([
                        Select::make('patient_id')
                            ->label('Patient')
                            ->live()
                            ->options(function () {
                                return Patient::query()
                                    ->get()
                                    ->mapWithKeys(function ($patient) {
                                        return [$patient->id => $patient->first_name . ' ' . $patient->last_name];
                                    })
                                    ->toArray();
                            })
                            ->disabledOn('edit')
                            ->required(),
                        Forms\Components\DateTimePicker::make('date')
                            ->default(Carbon::now())
                            ->disabledOn('edit')
                            ->required(),
                    ])->columns(2),
                
                Section::make('Patient Information')
                    ->schema([
                        Forms\Components\Fieldset::make('Patient')
                            ->schema([
                                Forms\Components\Placeholder::make('patient_name')
                                    ->label('Patient Name')
                                    ->content(function (Forms\Get $get) {
                                        $patientId = $get('patient_id');
                                        if (!$patientId) return '-';
                                        
                                        $patient = Patient::find($patientId);
                                        return $patient ? $patient->first_name . ' ' . $patient->middle_name . ' ' . $patient->last_name : '-';
                                    }),
                                Forms\Components\Placeholder::make('gender')
                                    ->label('Gender')
                                    ->content(function (Forms\Get $get) {
                                        $patientId = $get('patient_id');
                                        if (!$patientId) return '-';

                                        $patient = Patient::find($patientId);
                                        if (!$patient) return '-';

                                        $enum = SexEnum::tryFrom($patient->sex);
                                        return $enum ? $enum->getLabel() : '-';
                                    }),
                                Forms\Components\Placeholder::make('civil_status')
                                    ->label('Civil Status')
                                    ->content(function (Forms\Get $get) {
                                        $patientId = $get('patient_id');
                                        if (!$patientId) return '-';

                                        $patient = Patient::find($patientId);
                                        if (!$patient) return '-';

                                        $enum = CivilStatusEnum::tryFrom($patient->civil_status);
                                        return $enum ? $enum->getLabel() : '-';
                                    }),
                                Forms\Components\Placeholder::make('age')
                                    ->label('Age')
                                    ->content(function (Forms\Get $get) {
                                        $patientId = $get('patient_id');
                                        if (!$patientId) return '-';
                                        
                                        $patient = Patient::find($patientId);
                                        return $patient ? $patient->age : '-';
                                    }),
                                Forms\Components\Placeholder::make('educational_attainment')
                                    ->label('Educational Attainment')
                                    ->content(function (Forms\Get $get) {
                                        $patientId = $get('patient_id');
                                        if (!$patientId) return '-';

                                        $patient = Patient::find($patientId);
                                        if (!$patient) return '-';

                                        $enum = EducationalAttainmentEnum::tryFrom($patient->educational_attainment);
                                        return $enum ? $enum->getLabel() : '-';
                                    }),
                                Forms\Components\Placeholder::make('birth_date')
                                    ->label('Date of Birth')
                                    ->content(function (Forms\Get $get) {
                                        $patientId = $get('patient_id');
                                        if (!$patientId) return '-';
                                        
                                        $patient = Patient::find($patientId);
                                        return $patient ? Carbon::parse($patient->birth_date)->format('M d, Y') : '-';
                                    }),
                            ])
                            ->columns(3),
                        Fieldset::make('Medical History')
                            ->schema([
                                Forms\Components\Placeholder::make('blood_pressure')
                                    ->label('Blood Pressure (mm Hg)')
                                    ->content(function (Forms\Get $get) {
                                        $patientId = $get('patient_id');
                                        if (!$patientId) return '-';
                                        
                                        $patient = Patient::find($patientId);
                                        return $patient ? $patient->blood_pressure : '-';
                                    }),
                                Forms\Components\Placeholder::make('sugar_level')
                                    ->label('Sugar Level (mm/dl)')
                                    ->content(function (Forms\Get $get) {
                                        $patientId = $get('patient_id');
                                        if (!$patientId) return '-';
                                        
                                        $patient = Patient::find($patientId);
                                        return $patient ? $patient->sugar_level : '-';
                                    }),
                                Forms\Components\Placeholder::make('height')
                                    ->label('Height (cm)')
                                    ->content(function (Forms\Get $get) {
                                        $patientId = $get('patient_id');
                                        if (!$patientId) return '-';
                                        
                                        $patient = Patient::find($patientId);
                                        return $patient ? $patient->height : '-';
                                    }),
                                Forms\Components\Placeholder::make('weight')
                                    ->label('Weight (kg)')
                                    ->content(function (Forms\Get $get) {
                                        $patientId = $get('patient_id');
                                        if (!$patientId) return '-';
                                        
                                        $patient = Patient::find($patientId);
                                        return $patient ? $patient->weight : '-';
                                    }),
                                Forms\Components\Placeholder::make('bmi')
                                    ->label('BMI')
                                    ->content(function (Forms\Get $get) {
                                        $patientId = $get('patient_id');
                                        if (!$patientId) return '-';
                                        
                                        $patient = Patient::find($patientId);
                                        return $patient ? $patient->bmi : '-';
                                    }),
                                Forms\Components\Placeholder::make('bmi_category')
                                    ->label('BMI Category')
                                    ->content(function (Forms\Get $get) {
                                        $patientId = $get('patient_id');
                                        if (!$patientId) return '-';
                                        
                                        $patient = Patient::find($patientId);
                                        return $patient ? $patient->bmi_category : '-';
                                    }),
                                Forms\Components\Placeholder::make('health_statuses')
                                    ->label('Health Statuses')
                                    ->content(function (Forms\Get $get) {
                                        $patientId = $get('patient_id');
                                        if (!$patientId) return '-';

                                        $patient = Patient::find($patientId);
                                        if (!$patient) return '-';

                                        $statuses = $patient->health_statuses;
                                        $statusesArray = is_string($statuses) ? explode(',', $statuses) : (array) $statuses;
                                        $statusesArray = array_filter(array_map('trim', $statusesArray));

                                        return implode(', ', $statusesArray);
                                    }),
                                Forms\Components\Placeholder::make('medication_maintenance')
                                    ->label('Medication/Maintenance')
                                    ->content(function (Forms\Get $get) {
                                        $patientId = $get('patient_id');
                                        if (!$patientId) return '-';

                                        $patient = Patient::find($patientId);
                                        if (!$patient) return '-';

                                        $meds = $patient->medication_maintenance;
                                        $medsArray = is_string($meds) ? explode(',', $meds) : (array) $meds;
                                        $medsArray = array_filter(array_map('trim', $medsArray));

                                        return implode(', ', $medsArray);
                                    }),
                                Forms\Components\Placeholder::make('category')
                                    ->label('Category')
                                    ->content(function (Forms\Get $get) {
                                        $patientId = $get('patient_id');
                                        if (!$patientId) return '-';
                                        
                                        $patient = Patient::find($patientId);
                                        return $patient ? $patient->category->name : '-';
                                    }),
                            ])
                            ->columns(3)
                    ])->columns(3)
                    ->visible(fn (Forms\Get $get) => (bool) $get('patient_id')),

                Section::make('Consultation Details')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->placeholder('Purok')  
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Fieldset::make()
                            ->schema([
                                Forms\Components\ToggleButtons::make('disability')
                                    ->label('With Disability?')
                                    ->boolean()
                                    ->reactive()
                                    ->inline(),
                                Forms\Components\ToggleButtons::make('philhealth')
                                    ->label('With Philhealth?')
                                    ->boolean()
                                    ->inline(),
                                Forms\Components\ToggleButtons::make('member_of_4ps')
                                    ->label('4ps member?')
                                    ->boolean()
                                    ->inline(),
                                Forms\Components\ToggleButtons::make('nhts_member')
                                    ->label('NHTS Member?')
                                    ->boolean()
                                    ->inline(),
                                Forms\Components\ToggleButtons::make('birth_plan')
                                    ->label('Birth planned?')
                                    ->boolean()
                                    ->inline(),
                                Forms\Components\ToggleButtons::make('type')
                                    ->inline()
                                    ->inlineLabel(false)
                                    ->options(fn () => ConsultationFormOptionServices::getTypeOptions()),
                            ])
                            ->columns(3),
                        Forms\Components\Fieldset::make('Mother Information')
                            ->schema([
                                Forms\Components\TextInput::make('mother_first_name')
                                    ->label('First name'),
                                Forms\Components\TextInput::make('mother_last_name')
                                    ->label('Last Name'),
                                Forms\Components\TextInput::make('mother_middle_name')
                                    ->label('Middle Name'),
                            ])
                            ->columns(3),
                        Forms\Components\Fieldset::make('Child Information')
                            ->schema([
                                Forms\Components\TextInput::make('child_weight')
                                    ->label('Child Weight')
                                    ->numeric() 
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('child_order')
                                    ->label('Child Order')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(20)
                                    ->columnSpan(2),
                                Forms\Components\ToggleButtons::make('mother_status')
                                    ->label('Mother\'s Status')  
                                    ->helperText('TT/TD Status of mother/CPAB')
                                    ->boolean()
                                    ->inline(),
                                Forms\Components\ToggleButtons::make('hepa_b')
                                    ->label('Hepa B')
                                    ->boolean()
                                    ->inline(),
                                Forms\Components\ToggleButtons::make('nbs')
                                    ->label('NBS')
                                    ->boolean()
                                    ->inline(),
                                Forms\Components\ToggleButtons::make('prenatal_dates')
                                    ->label('Add Prenatal Dates')
                                    ->boolean()
                                    ->live()
                                    ->inline(),
                            ])
                            ->columns(4),
                        Forms\Components\Fieldset::make('Immunization')
                            ->visible(fn (Forms\Get $get) => $get('prenatal_dates'))
                            ->schema([
                                Forms\Components\DatePicker::make('bcg_date')
                                    ->label('BCG'),
                                Forms\Components\DatePicker::make('prenatal_date')
                                    ->label('Prenatal Date'),
                                Forms\Components\DatePicker::make('polio_date')
                                    ->label('Polio'),
                                Forms\Components\DatePicker::make('ipv_date')
                                    ->label('IPV'),
                                Forms\Components\DatePicker::make('pcv_date')
                                    ->label('PCV'),
                                Forms\Components\DatePicker::make('measles_date')
                                    ->label('Measles'),
                                Forms\Components\DatePicker::make('mmr_date')
                                    ->label('MMR')
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                        Forms\Components\Fieldset::make()
                            ->schema([
                                Forms\Components\CheckboxList::make('disabilities')
                                    ->required(fn (Forms\Get $get) => $get('disability'))
                                    ->disabled(fn (Forms\Get $get) => ! $get('disability'))
                                    ->columnSpanFull()
                                    ->gridDirection('row')
                                    ->columns(2)
                                    ->options(fn () => ConsultationFormOptionServices::getDisabilitiesOptions()),
                                Forms\Components\TextInput::make('other_diseases')
                                    ->columnSpanFull(),
                            ]),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient_full_name')
                    ->label('Patient Name')
                    ->getStateUsing(fn ($record) => $record->patient->last_name . ', ' . $record->patient->first_name),
                Tables\Columns\TextColumn::make('date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
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