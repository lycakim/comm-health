<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use App\Models\User;
use Filament\Tables;
use App\Enums\SexEnum;
use App\Models\Patient;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Barangay;
use App\Models\Category;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Schema;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Forms\Components\CheckboxList;
use App\Services\PatientFormOptionsServices;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Actions\Action;
use App\Filament\Resources\PatientResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    // protected static ?int $navigationSort = 1;

    protected static ?string $description = 'View and manage patient records';

    public static function getNavigationSort(): ?int
    {
        if (auth()->user()->isMHO()) {
            return 1;
        }
        return 1;
    }

    public static function getPluralModelLabel(): string
    {
        if (auth()->user()->isMHO() || auth()->user()->isBHW() || auth()->user()->isMidwife()) {
            return 'Patient Records';
        }
        return 'Patients';
    }

    public static function canAccess(): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isMHO() || auth()->user()->isBHW() || auth()->user()->isMidwife();
    }

    public static function form(Form $form): Form
    {
        $calculateBMI = function ($state, callable $set, $get) {
            $height = $get('height');
            $weight = $get('weight');
            
            if (empty($height) || empty($weight) || $height <= 0 || $weight <= 0) {
                $set('bmi', null);
                return;
            }
            
            $heightInMeters = $height / 100;
            $bmi = $weight / ($heightInMeters * $heightInMeters);
            $bmiCategory = match(true) {
                $bmi < 18.5 => 'Underweight',
                $bmi < 25 => 'Normal weight',
                $bmi < 30 => 'Overweight',
                default => 'Obese'
            };
            $set('bmi', round($bmi, 2));
            $set('bmi_category', $bmiCategory);
        };

        return $form
            ->schema([
                Section::make('Patients Statistics')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        ViewField::make('rating')
                            ->dehydrated(false)
                            ->view('filament.forms.components.patient-stats')
                            ->viewData([
                                'totalPatients' => 12,
                                'maternalCount' => 5,
                                'childCount' => 5,
                                'seniorCount' => 4,
                                'chronicCount' => 1,
                                'recentActivities' => 0,
                            ])
                    ]),

                Section::make()
                    ->schema([
                        Fieldset::make('Personal Information')
                            ->schema([
                                TextInput::make('first_name')->required(),
                                TextInput::make('last_name')->required(),
                                // Select::make('resident_id')
                                //     ->label('Resident')
                                //     ->options(User::query()->get()->pluck('name', 'id')->toArray())
                                //     ->columnSpanFull(),
                                TextInput::make('middle_name'),
                                Select::make('relationship_to_head_of_family')
                                    ->label('Relationship to the Head of the Family')
                                    ->columnSpan(fn (Get $get) => $get('relationship_to_head_of_family') === 'other' ? 2 : 'full')
                                    ->options(fn () => PatientFormOptionsServices::getPatientRelationships())
                                    ->live()
                                    ->required(fn (Get $get) => $get('relationship_to_head_of_family') !== 'other'),

                                TextInput::make('relationship_to_head_of_family_other')
                                    ->label('Please specify relationship')
                                    ->columnSpan(1)
                                    ->required(fn (Get $get) => $get('relationship_to_head_of_family') === 'other')
                                    ->visible(fn (Get $get) => $get('relationship_to_head_of_family') === 'other'),
                                Textarea::make('place_of_birth')
                                    ->required()
                                    ->columnSpanFull(),
                                DatePicker::make('birth_date')
                                    ->label('Date of Birth')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        $set('age', $state ? Carbon::parse($state)->age : null);
                                    }),
                                TextInput::make('age')
                                    ->label('Age (Years)')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->formatStateUsing(function ($record) {
                                        if ($record && $record->birth_date) {
                                            return Carbon::parse($record->birth_date)->age;
                                        }
                                        return null;
                                    }),
                                Select::make('sex')
                                    ->label('Gender')
                                    ->options([
                                        'male' => 'Male',
                                        'female' => 'Female',
                                    ])
                                    ->required(),
                                Select::make('civil_status')
                                    ->label('Civil Status')
                                    ->options(fn () => PatientFormOptionsServices::getPatientStatus())
                                    ->required(),
                                Select::make('educational_attainment')
                                    ->label('Educational Attainment')
                                    ->options(fn () => PatientFormOptionsServices::getPatientEducationalAttainment())
                                    ->required(),
                                Select::make('occupation')
                                    ->label('Occupation')
                                    ->options(fn () => PatientFormOptionsServices::getPatientOccupation())
                                    ->required(),
                                Select::make('barangay_id')
                                    ->label('Barangay')
                                    ->options(Barangay::query()->get()->pluck('name', 'id')->toArray())
                                    ->preload()
                                    ->columnSpan(2),
                                Select::make('category_id')
                                    ->label('Category')
                                    ->options(Category::query()->get()->pluck('name', 'id')->toArray())
                                    ->preload(),
                            ])
                            ->columns(3),
                        Fieldset::make('Women of Reproductive Age (15-49 yrs old)')
                            ->schema([
                                ToggleButtons::make('pregnant')
                                    ->label('Pregnant?')
                                    ->boolean()
                                    ->live()
                                    ->inline(),
                                TextInput::make('weeks_pregnant')
                                    ->label('Weeks')
                                    ->minValue(0)   
                                    ->maxValue(52) 
                                    ->default(0) 
                                    ->disabled(fn (Get $get) => ! $get('pregnant'))
                                    ->numeric(),
                                TextInput::make('months_pregnant')
                                    ->minValue(0)
                                    ->maxValue(12)
                                    ->default(0)
                                    ->disabled(fn (Get $get) => ! $get('pregnant'))
                                    ->label('Months'),
                                Fieldset::make('Current Family Planning Method')
                                    ->schema([
                                        CheckboxList::make('current_family_planning_method')
                                            ->label(false)
                                            ->columns(2)
                                            ->columnSpanFull()
                                            ->gridDirection('row')
                                            ->options(fn () => PatientFormOptionsServices::getFamilyPlanningMethods())
                                    ]),
                                Select::make('child_health_status')
                                    ->label('Child Health Status')
                                    ->columnSpanFull()
                                    ->options(fn () => PatientFormOptionsServices::getChildHealthStatus()),
                                Select::make('family_monthly_income')
                                    ->label('Family Monthly Income')
                                    ->preload()
                                    ->columnSpanFull()
                                    ->options(fn () => PatientFormOptionsServices::getFamilyMonthlyIncome())
                            ])
                            ->columns(3),
                        Fieldset::make('Religion')
                            ->schema([
                                ToggleButtons::make('ip')
                                    ->label('IP?')
                                    ->boolean()
                                    ->live()
                                    ->inline(),
                                TextInput::make('ip_type')
                                    ->label('Type')
                                    ->disabled(fn (Get $get) => ! $get('ip')),
                            ])
                            ->columns(2),
                        Fieldset::make('Housing Facilities')
                            ->schema([
                                TextInput::make('no_of_house')
                                    ->numeric()
                                    ->default(1),
                                ToggleButtons::make('with_fence')
                                    ->label('With Fence?')
                                    ->boolean()
                                    ->live()
                                    ->inline(),
                                Radio::make('house_type')
                                    ->columns(3)
                                    ->columnSpanFull()
                                    ->gridDirection('row')
                                    ->options(fn () => PatientFormOptionsServices::getPatientHouseTypes())
                            ])
                            ->columns(2)
                    ])
                    ->columns(3),
                    
                Section::make('Medical History')
                    ->collapsible()
                    ->schema([
                        Fieldset::make('Health Status')
                            ->schema([
                                CheckboxList::make('health_statuses')
                                    ->label(false)
                                    ->columns(3)
                                    ->columnSpanFull()
                                    ->gridDirection('row')
                                    ->options(fn () => PatientFormOptionsServices::getPatientHealthStatuses())
                            ]),
                        TextInput::make('blood_pressure')
                            ->numeric()
                            ->minValue(0)
                            ->label('Blood Pressure')
                            ->hint('mm Hg'),
                        TextInput::make('sugar_level')
                            ->label('Sugar Level')
                            ->columnSpan(2)
                            ->numeric()
                            ->hint('mg/dl'),
                        TextInput::make('height')
                            ->minValue(0)
                            ->numeric()
                            ->hint('cm')
                            ->label('Height')
                            ->reactive()
                            ->afterStateUpdated($calculateBMI),
                        TextInput::make('weight')
                            ->minValue(0)
                            ->columnSpan(2)
                            ->hint('kg')
                            ->numeric()
                            ->label('Weight')
                            ->reactive()
                            ->afterStateUpdated($calculateBMI),
                        TextInput::make('bmi')
                            ->label('BMI')
                            ->hint('kg/m²'),
                        TextInput::make('bmi_category')
                            ->label('BMI Category')
                            ->hint('kg/m²'),
                        ToggleButtons::make('trained_for_first_aid')
                            ->label('Trained for First Aid?')
                            ->grouped()
                            ->required()
                            ->default(false)
                            ->boolean()
                            ->inline(),
                        Fieldset::make('Medication/Maintenance')
                            ->schema([
                                CheckboxList::make('medication_maintenance')
                                    ->label(false)
                                    ->columns(3)
                                    ->columnSpanFull()
                                    ->gridDirection('row')
                                    ->options(fn () => PatientFormOptionsServices::getMedicationMaintenance())
                            ]),
                    ])
                    ->columns(3),
                
                Section::make('Other Information')
                    ->collapsible()
                    ->schema([
                        Fieldset::make('Source of Water Supply')
                            ->schema([
                                Radio::make('water_supply_sources')
                                    ->label(false)
                                    ->columns(3)
                                    ->columnSpanFull()
                                    ->gridDirection('row')
                                    ->options(fn () => PatientFormOptionsServices::getPatientWaterSupplySources())
                            ]),
                        Fieldset::make('Type of Toilet')
                            ->schema([
                                Radio::make('toilet_types')
                                    ->label(false)
                                    ->columns(3)
                                    ->columnSpanFull()
                                    ->gridDirection('row')
                                    ->options(fn () => PatientFormOptionsServices::getPatientToiletTypes())
                            ]),
                        Fieldset::make('Drainage and Disposal')
                            ->schema([
                                Radio::make('drainage_disposals')
                                    ->label(false)
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->gridDirection('row')
                                    ->options(fn () => PatientFormOptionsServices::getPatientDrainageDisposals())
                            ]),
                        Fieldset::make('Livestock Commonly Raised')
                            ->schema([
                                CheckboxList::make('livestock')
                                    ->label(false)
                                    ->columns(3)
                                    ->columnSpanFull()
                                    ->gridDirection('row')
                                    ->options(fn () => PatientFormOptionsServices::getPatientLivestock())
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Full Name')
                    ->searchable(['first_name', 'last_name'])
                    ->getStateUsing(function ($record) {
                        return $record->first_name . ' ' . $record->last_name;
                    }),
                TextColumn::make('sex')
                    ->formatStateUsing(fn ($state) => SexEnum::tryFrom($state)?->getLabel() ?? ucfirst($state))
                    ->badge()
                    ->color(fn (string $state): string => SexEnum::tryFrom($state)?->getColor() ?? 'gray'),
                TextColumn::make('age')
                    ->getStateUsing(function ($record) {
                        return Carbon::parse($record->birth_date)->age;
                    })
                    ->label('Age'),
                TextColumn::make('barangay.name')
                    ->label('Assigned Barangay')
                    ->searchable()
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('barangay_id')
                    ->label('Barangay')
                    ->relationship('barangay', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('sex')
                    ->label('Sex')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                    ])    
                    ->searchable(),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->color('warning'),
                    Tables\Actions\ViewAction::make()
                        ->color('primary'),
                    Tables\Actions\Action::make('add_new_user')
                        ->label('Create Access')
                        ->hidden(fn ($record) => $record->user_id)
                        ->accessSelectedRecords()
                        ->color('gray')
                        ->icon('heroicon-o-plus')
                        ->form([
                            TextInput::make('email')
                                ->label('Email Address')
                                ->email()
                                ->required()
                                ->unique('users', 'email')
                                ->suffixAction(
                                    Action::make('fill_from_name')
                                        ->label('Suggest from name')
                                        ->icon('heroicon-o-sparkles')
                                        ->action(function (Set $set, $record) {
                                            if ($record && $record->first_name && $record->last_name) {
                                                $firstName = strtolower($record->first_name);
                                                $lastName = strtolower($record->last_name);
                                                $suggestedEmail = "{$firstName}.{$lastName}@gmail.com";
                                                $set('email', $suggestedEmail);
                                            }
                                        })
                                ),
                            TextInput::make('password')
                                ->label('Password')
                                ->password()
                                ->required()
                                ->minLength(8)
                                ->revealable()
                                ->helperText('Please take note of your password. You will need it to access your medical records.')
                                ->confirmed(),
                            TextInput::make('password_confirmation')
                                ->label('Confirm Password')
                                ->password()
                                ->revealable()
                                ->required(),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Create Patient Access')
                        ->modalDescription(fn ($record) => new \Illuminate\Support\HtmlString("You are about to create login credentials for this <strong>{$record->first_name} {$record->last_name}</strong>. They will be able to access their medical records online."))
                        ->modalSubmitActionLabel('Yes, create access')
                        ->modalIcon('heroicon-o-user-plus')
                        ->action(function (array $data, $record) {
                            $user = User::create([
                                'name' => $record->first_name . ' ' . $record->last_name,
                                'email' => $data['email'],
                                'password' => Hash::make($data['password']),
                            ]);
    
                            $record->user_id = $user->id;
                            $record->save();
                        
                            Notification::make()
                                ->title('User created successfully')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                if (auth()->user()->isAdmin()) {
                    return $query->latest();
                } else {
                    $userBarangayIds = auth()->user()->barangays->pluck('id')->toArray();
                    
                    if (Schema::hasColumn('patients', 'barangay_id')) {
                        return $query->whereIn('barangay_id', $userBarangayIds)->latest();
                    }

                    return $query
                        ->where(function($query) use ($userBarangayIds) {
                            $query->whereHas('user', function ($subquery) use ($userBarangayIds) {
                                $subquery->whereHas('barangays', function ($barangayQuery) use ($userBarangayIds) {
                                    $barangayQuery->whereIn('barangays.id', $userBarangayIds);
                                });
                            });

                            if (Schema::hasColumn('patients', 'barangay_id')) {
                                $query->orWhereIn('barangay_id', $userBarangayIds);
                            }
                        })
                        ->latest();
                }
            });
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
            'index' => Pages\ListPatients::route('/'),
            'create' => Pages\CreatePatient::route('/create'),
            'edit' => Pages\EditPatient::route('/{record}/edit'),
            'view' => Pages\ViewPatient::route('/{record}/view'),
        ];
    }

    protected function calculateBMI($height, $weight)
    {
        if (empty($height) || empty($weight) || $height <= 0 || $weight <= 0) {
            return null;
        }
        
        // Convert height from cm to meters
        $heightInMeters = $height / 100;
        
        // Calculate BMI: weight (kg) / height² (m²)
        $bmi = $weight / ($heightInMeters * $heightInMeters);
        
        // Round to 2 decimal places
        return round($bmi, 2);
    }
}