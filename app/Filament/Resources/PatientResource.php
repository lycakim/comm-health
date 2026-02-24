<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use App\Models\User;
use Filament\Tables;
use App\Models\Purok;
use App\Enums\SexEnum;
use App\Enums\RoleEnum;
use App\Models\Patient;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Barangay;
use App\Models\Category;
use Filament\Forms\Form;
use App\Models\Occupation;
use Filament\Tables\Table;
// use App\Traits\HasUserTypeUrls;
use Filament\Resources\Resource;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Schema;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Collection;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Forms\Components\CheckboxList;
use App\Services\PatientFormOptionsServices;
use App\Exports\PatientsExport;
use App\Services\PDFGenerationService;
use App\Services\PatientImportService;
use Maatwebsite\Excel\Facades\Excel;
use App\Jobs\ImportPatientsJob;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Actions\Action;
use App\Filament\Resources\PatientResource\Pages;
use Filament\Tables\Actions\Action as TablesAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

class PatientResource extends Resource
{
    // Removed HasUserTypeUrls trait - using standard Filament navigation
    // use HasUserTypeUrls;

    protected static ?string $model = Patient::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    // protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Residents Record';
    
    protected static ?string $modelLabel = 'Resident';
    
    protected static ?string $pluralModelLabel = 'Residents';

    protected static ?string $slug = 'residents';

    protected static ?string $breadcrumb = 'Residents Record';

    protected static ?string $description = 'View and manage residents records';

    // Removed custom mount redirect - let Filament handle navigation naturally
    // public function mount(): void
    // {
    //     if (self::currentUser()->isMHO()) {
    //         $this->redirect(PatientResource::getUrl('all'));
    //     }
    // }

    public static function getNavigationSort(): ?int
    {
        if (self::currentUser()->isMHO()) {
            return 1;
        }
        return 1;
    }

    public static function getPluralModelLabel(): string
    {
        $user = self::currentUser();
        if ($user->isMHO() || $user->isBHW() || $user->isMidwife()) {
            return 'Residents Record';
        }
        return 'Residents';
    }

    public static function canAccess(): bool
    {
        return in_array(self::currentUser()->role, [
            RoleEnum::ADMIN,
            RoleEnum::MHO,
            RoleEnum::BHW,
            RoleEnum::MIDWIFE
        ]);
    }

    public static function canCreate(): bool
    {
        $user = self::currentUser();
        
        // MHO/Admin can always create
        if ($user->isMHO() || $user->isAdmin()) {
            return true;
        }
        
        // Other users (BHW, Midwife) need assigned barangay_id
        return !is_null($user->barangay_id);
    }

    public static function canEdit(Model $record): bool
    {
        $user = self::currentUser();
        if ($user->isMHO() || $user->isAdmin()) {
            return true;
        }
        if ($user->isBHW() || $user->isMidwife()) {
            if (is_null($user->barangay_id)) {
                return false;
            }
            return $record->barangay_id === $user->barangay_id;
        }
        return false;
    }

    public static function canView(Model $record): bool
    {
        $user = self::currentUser();
        if ($user->isMHO() || $user->isAdmin()) {
            return true;
        }
        if ($user->isBHW() || $user->isMidwife()) {
            if (is_null($user->barangay_id)) {
                return false;
            }
            return $record->barangay_id === $user->barangay_id;
        }
        return false;
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
                Section::make('Residents Statistics')
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
                                // Select::make('resident_id')
                                //     ->label('Resident')
                                //     ->options(User::query()->get()->pluck('name', 'id')->toArray())
                                //     ->columnSpanFull(),
                                TextInput::make('first_name')
                                    ->required()
                                    ->placeholder('Juan')
                                    ->live(onBlur: false)
                                    ->debounce(500),
                                TextInput::make('middle_name')
                                    ->hint('optional')
                                    ->placeholder('Manuel')
                                    ->live(onBlur: false)
                                    ->debounce(500),
                                TextInput::make('last_name')
                                    ->required()
                                    ->placeholder('Dela Cruz')
                                    ->live(onBlur: false)
                                    ->debounce(500),
                                TextInput::make('suffix')->hint('Jr., Sr., II, III, etc.'),
                                ViewField::make('matching_patients')
                                    ->label('Matching Existing Patients')
                                    ->dehydrated(false)
                                    ->view('filament.forms.components.patient-name-autocomplete')
                                    ->visible(fn (Get $get, $livewire) =>
                                        $livewire->getRecord() === null &&
                                        (
                                            (strlen(trim($get('first_name') ?? '')) >= 2) ||
                                            (strlen(trim($get('middle_name') ?? '')) >= 2) ||
                                            (strlen(trim($get('last_name') ?? '')) >= 2)
                                        )
                                    )
                                    ->viewData(fn (Get $get, $livewire) => [
                                        'matchingPatients' => self::searchMatchingPatients(
                                            $get('first_name'),
                                            $get('middle_name'),
                                            $get('last_name')
                                        ),
                                        'currentFirstName' => $get('first_name'),
                                        'currentMiddleName' => $get('middle_name'),
                                        'currentLastName' => $get('last_name'),
                                        'isCreate' => $livewire->getRecord() === null,
                                    ])
                                    ->columnSpanFull(),
                                Select::make('sex')
                                    ->label('Gender')
                                    ->searchable()
                                    ->live()
                                    ->lazy()
                                    ->preload()
                                    ->columnSpan(2)
                                    ->options([
                                        'male' => 'Male',
                                        'female' => 'Female',
                                    ])
                                    ->required(),
                                TextInput::make('contact_number')
                                    ->label('Contact Number')
                                    ->columnSpan(2)
                                    ->required()
                                    ->rule('regex:/^(09\d{9}|9\d{9}|639\d{9})$/')
                                    ->hint('Format: 09123456789')
                                    ->afterStateHydrated(function ($component, $state) {
                                        // Normalize for display if needed
                                        if ($state && str_starts_with($state, '63')) {
                                            $component->state('0' . substr($state, 2));
                                        }
                                    })
                                    ->dehydrateStateUsing(function ($state) {
                                        // Normalize before saving to DB
                                        // Always store as 639XXXXXXXXX
                                        $clean = preg_replace('/\D/', '', $state);

                                        if (str_starts_with($clean, '09')) {
                                            return '63' . substr($clean, 1);
                                        }

                                        if (str_starts_with($clean, '9')) {
                                            return '63' . $clean;
                                        }

                                        return $clean;
                                    })
                                    ->mask('99999999999'), // (optional) enforce 11 digits on screen
                                Select::make('relationship_to_head_of_family')
                                    ->label('Relationship to the Head of the Family')
                                    ->live()
                                    ->columnSpan(2)
                                    // ->columnSpan(fn (Get $get) => $get('relationship_to_head_of_family') === 'Other' ? 2 : 2)
                                    ->default('Household Head')
                                    ->options(fn () => collect(PatientFormOptionsServices::getPatientRelationships())->sort()->toArray())
                                    ->preload()
                                    ->searchable()
                                    ->required(),
                                    // ->required(fn (Get $get) => $get('relationship_to_head_of_family') !== 'Other'),
                                // TextInput::make('relationship_to_head_of_family_other')
                                //     ->label('Please specify relationship')
                                //     ->columnSpan(2)
                                //     ->required(fn (Get $get) => $get('relationship_to_head_of_family') === 'Other')
                                //     ->disabled(fn (Get $get) => $get('relationship_to_head_of_family') !== 'Other'),
                                Select::make('household_head_id')
                                    ->label('Household Head')
                                    ->relationship(
                                        'householdHead',
                                        'first_name',
                                        function ($query, $livewire) {
                                            // Exclude self
                                            if ($livewire->getRecord()) {
                                                $query = $query->where('id', '!=', $livewire->getRecord()->id);
                                            }
                                            // Only allow within the same barangay if NOT MHO
                                            $currentUser = auth()->user();
                                            if ($currentUser && !in_array($currentUser->role, [\App\Enums\RoleEnum::MHO, \App\Enums\RoleEnum::ADMIN])) {
                                                $barangayId = $currentUser->barangay_id;
                                                if ($barangayId) {
                                                    $query = $query->where('barangay_id', $barangayId);
                                                }
                                            }
                                            return $query;
                                        }
                                    )
                                    ->getOptionLabelFromRecordUsing(fn ($record) => ($record->last_name ?? '') . ', ' . ($record->first_name ?? '') . ($record->middle_name ? ' ' . $record->middle_name : ''))
                                    ->searchable(['first_name', 'last_name', 'middle_name'])
                                    ->preload()
                                    ->required()
                                    ->columnSpan(2),
                                Textarea::make('place_of_birth')
                                    ->required()
                                    ->columnSpanFull(),
                                DatePicker::make('birth_date')
                                    ->label('Date of Birth')
                                    ->required()
                                    ->displayFormat('M d, Y')
                                    ->native(false)
                                    ->firstDayOfWeek(7)
                                    ->maxDate(now())
                                    ->rule('before_or_equal:' . now()->format('Y-m-d'))
                                    ->live()
                                    ->debounce(500)
                                    ->afterStateUpdated(function (callable $set, Get $get, $state) {
                                        if ($state) {
                                            $birthDate = Carbon::parse($state);
                                            
                                            // Calculate age in years for category assignment and storage
                                            $ageInYears = $birthDate->age;
                                            $set('age', $ageInYears);
                                            
                                            // Auto-assign category based on age in years (always update when birthdate changes)
                                            $categoryId = self::getCategoryIdByAge($ageInYears);
                                            $set('category_id', $categoryId);
                                        } else {
                                            $set('age', null);
                                        }
                                    }),
                                TextInput::make('age')
                                    ->label('Age')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->formatStateUsing(function ($record) {
                                        if ($record && $record->birth_date) {
                                            $birthDate = Carbon::parse($record->birth_date);
                                            $now = Carbon::now();
                                            
                                            $years = (int) $birthDate->diffInYears($now);
                                            $months = (int) $birthDate->copy()->addYears($years)->diffInMonths($now);
                                            
                                            $parts = [];
                                            if ($years > 0) {
                                                $parts[] = $years . ' ' . ($years === 1 ? 'year' : 'years');
                                            }
                                            if ($months > 0) {
                                                $parts[] = $months . ' ' . ($months === 1 ? 'month' : 'months');
                                            }
                                            
                                            return !empty($parts) ? implode(' ', $parts) : '0 months';
                                        }
                                        return null;
                                    }),
                                Select::make('category_id')
                                    ->label('Category')
                                    ->searchable()
                                    ->lazy()
                                    ->options(
                                        Category::query()
                                            ->get()
                                            ->mapWithKeys(fn ($category) => [
                                                $category->id => $category->description 
                                                    ? "{$category->name} - {$category->description}"
                                                    : $category->name
                                            ])
                                            ->toArray()
                                    )
                                    ->preload(),
                                Select::make('civil_status')
                                    ->label('Civil Status')
                                    ->searchable()
                                    ->options(fn () => collect(PatientFormOptionsServices::getPatientStatus())->sort()->toArray())
                                    ->required(),
                                Select::make('educational_attainment')
                                    ->label('Educational Attainment')
                                    ->searchable()
                                    ->options(fn () => collect(PatientFormOptionsServices::getPatientEducationalAttainment())->sort()->toArray())
                                    ->required(),
                                Select::make('occupation_id')
                                    ->label('Occupation')
                                    ->searchable()
                                    ->options(fn () => Occupation::query()->get()->pluck('name', 'id')->sort()->toArray())
                                    ->createOptionForm(function () {
                                        return [
                                            TextInput::make('name')->required(),
                                            Textarea::make('description'),
                                        ];
                                    })
                                    ->createOptionUsing(function (array $data): int {
                                        return Occupation::create($data)->getKey();
                                    })
                                    ->required(),
                                Select::make('barangay_id')
                                    ->label('Barangay')
                                    ->searchable()
                                    ->options(Barangay::query()->get()->pluck('name', 'id')->sort()->toArray())
                                    ->preload()
                                    ->default(function () {
                                        return Auth::user()->barangay_id;
                                    })
                                    ->disabled(fn () => (bool) Auth::user()->barangay_id)
                                    ->dehydrated(true)
                                    ->live(),
                                Select::make('purok_id')
                                    ->label('Purok')
                                    ->searchable()
                                    ->disabled(fn (Get $get) => ! $get('barangay_id'))
                                    ->options(function (Get $get) {
                                        $barangayId = $get('barangay_id');
                                        return Purok::query()->where('barangay_id', $barangayId)->get()->pluck('name', 'id')->sort()->toArray();
                                    })
                                    ->preload()
                                    ->createOptionForm(function (Get $get) {
                                        $barangayId = $get('barangay_id');
                                        
                                        return [
                                            TextInput::make('name')
                                                ->unique('puroks', 'name', modifyRuleUsing: function ($rule, Get $get) {
                                                    $barangayId = $get('barangay_id');
                                                    return $rule->where('barangay_id', $barangayId);
                                                })
                                                ->required(),
                                            Select::make('barangay_id')
                                                ->label('Barangay')
                                                ->required()
                                                ->options(function () use ($barangayId) {
                                                    if (!$barangayId) {
                                                        return [];
                                                    }
                                                    
                                                    return Barangay::query()
                                                        ->where('id', $barangayId)
                                                        ->get()
                                                        ->pluck('name', 'id')
                                                        ->toArray();
                                                })
                                                ->default($barangayId)
                                                ->disabled(),
                                        ];
                                    })
                                    ->createOptionUsing(function (array $data, Get $get): int {
                                        $barangayId = $get('barangay_id');

                                        $data['barangay_id'] = $barangayId;
                                        
                                        return Purok::create($data)->getKey();
                                    }),
                            ])
                            ->columns(4),
                        Fieldset::make('Women of Reproductive Age (15-49 yrs old)')
                            ->schema([
                                ToggleButtons::make('pregnant')
                                    ->label('Pregnant?')
                                    ->boolean()
                                    ->default(false)
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
                                    ->label('Indigenous People?')
                                    ->default(false)
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
                                    ->default(false)
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
                            ->label('Blood Pressure')
                            ->hint('Format: 120/80')
                            ->required()
                            ->rule('regex:/^\d{2,3}\/\d{2,3}$/')
                            ->placeholder('120/80')
                            ->columnSpan(1)
                            ->dehydrateStateUsing(fn($state) => trim($state)),
                        TextInput::make('sugar_level')
                            ->label('Sugar Level')
                            ->columnSpan(1)
                            ->numeric()
                            ->hint('mg/dl'),
                        TextInput::make('height')
                            ->minValue(0)
                            ->numeric()
                            ->hint('cm')
                            ->label('Height')
                            ->columnSpan(1)
                            ->reactive()
                            ->afterStateUpdated($calculateBMI),
                        TextInput::make('weight')
                            ->minValue(0)
                            ->columnSpan(1)
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
            ->headerActions([
                TablesAction::make('exportToCSV')
                    ->label('Export')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->disabled(fn() => ! self::canCreate())
                    ->color('gray')
                    ->form([
                        Select::make('category')
                            ->label('Export Category')
                            ->options([
                                'residents_profiling' => 'Residents Profiling',
                                'maternal_child' => 'Maternal and Child Report',
                                'children_adolescent' => 'Children and Adolescent Reports',
                                'senior_citizens' => 'Senior Citizens Reports',
                                'maintenance' => 'Person with Maintenance Reports',
                                'pwds' => 'Person with Disabilities Reports',
                            ])
                            ->required()
                            ->default('residents_profiling'),
                        Select::make('format')
                            ->label('Export Format')
                            ->options([
                                'xlsx' => 'Excel (XLSX)',
                                'pdf' => 'PDF (Document)',
                            ])
                            ->default('xlsx')
                            ->required(),
                        Section::make('Filters')
                            ->schema([
                                TextInput::make('age_min')
                                    ->label('Minimum Age')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(150),
                                TextInput::make('age_max')
                                    ->label('Maximum Age')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(150),
                                Select::make('gender')
                                    ->label('Gender')
                                    ->options([
                                        'Male' => 'Male',
                                        'Female' => 'Female',
                                    ]),
                                Select::make('purok_id')
                                    ->label('Purok')
                                    ->relationship('purok', 'name')
                                    ->searchable()
                                    ->preload(),
                            ])
                            ->columns(2),
                    ])
                    ->action(function ($data) {
                        $query = Patient::with(['barangay', 'category', 'purok']);
                        $user = Auth::user();
                        // BHW/Midwife: filter by barangay; no barangay_id = no export
                        if ($user->isBHW() || $user->isMidwife()) {
                            if (is_null($user->barangay_id)) {
                                Notification::make()
                                    ->title('No barangay assigned')
                                    ->body('You must have an assigned barangay to export residents.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            $query->where('barangay_id', $user->barangay_id);
                        }
                        
                        // Apply AGE, GENDER, PUROK filters
                        if (!empty($data['age_min'])) {
                            $query->where('age', '>=', $data['age_min']);
                        }
                        if (!empty($data['age_max'])) {
                            $query->where('age', '<=', $data['age_max']);
                        }
                        if (!empty($data['gender'])) {
                            $query->where('sex', $data['gender']);
                        }
                        if (!empty($data['purok_id'])) {
                            $query->where('purok_id', $data['purok_id']);
                        }
                        
                        $barangay = $user->barangay_id ? Barangay::find($user->barangay_id) : null;
                        $barangayName = $barangay ? $barangay->name : '';
                        $brgy = $barangayName ? 'barangay_' . strtolower($barangayName) . '_' : '';
                        
                        $reportTitle = '';
                        $title = '';

                        switch ($data['category']) {
                            case 'residents_profiling':
                                $reportTitle =  $brgy . 'residents_profiling';
                                $title = 'Residents Information Records';
                                break;
                            case 'maternal_child':
                                $query->where('category_id', Category::where('name', 'LIKE', '%maternal and child%')->value('id'));
                                $reportTitle = $brgy . 'maternal_and_child_report';
                                $title = 'Maternal and Child Report';
                                break;
                            case 'children_adolescent':
                                $query->where('category_id', Category::where('name', 'LIKE', '%children and adolescent%')->value('id'));
                                $reportTitle = $brgy . 'children_and_adolescent_report';
                                $title = 'Children and Adolescent Report';
                                break;
                            case 'senior_citizens':
                                $query->where('category_id', Category::where('name', 'LIKE', '%senior citizen%')->value('id'));
                                $reportTitle = $brgy . 'senior_citizens_report';
                                $title = 'Senior Citizens Report';
                                break;
                            case 'maintenance':
                                $query->where('category_id', Category::where('name', 'LIKE', '%person with maintenance%')->value('id'));
                                $reportTitle = $brgy . 'person_with_maintenance_report';
                                $title = 'Person with Maintenance Report';
                                break;
                            case 'pwds':
                                $query->where('category_id', Category::where('name', 'LIKE', '%person with disabilities%')->value('id'));
                                $reportTitle = $brgy . 'person_with_disabilities_report';
                                $title = 'Person with Disabilities Report';
                                break;
                            case 'all':
                            default:
                                $reportTitle = $brgy . 'all_patients';
                                $title = 'All Residents';
                                break;
                        }

                        $patients = $query->get();

                        if ($patients->isEmpty()) {
                            Notification::make()
                                ->title('No patients found for ' . $title)
                                ->body('Please select a different category')
                                ->danger()
                                ->send();
                            return;
                        }

                        $user = Auth::user();
                        $barangay = $user->barangay_id ? Barangay::find($user->barangay_id) : null;

                        // Handle PDF export
                        if ($data['format'] === 'pdf') {
                            $pdfService = app(PDFGenerationService::class);
                            $pdf = $pdfService->generatePatientListPdf($patients, $title, $barangay);
                            $filename = $reportTitle . '_' . date('Y-m-d_His') . '.pdf';
                            
                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                $filename,
                                ['Content-Type' => 'application/pdf']
                            );
                        }

                        // Handle XLSX export
                        if ($data['format'] === 'xlsx') {
                            $user = Auth::user();
                            $user->load('barangay');
                            return Excel::download(
                                new PatientsExport($patients, $title, $barangay, $user->getPreparedByLabelForExport()),
                                $reportTitle . '_' . date('Y-m-d_His') . '.xlsx',
                                \Maatwebsite\Excel\Excel::XLSX
                            );
                        }

                        // Handle CSV export
                        return response()->streamDownload(function () use ($patients, $data, $title, $barangay) {
                            $csv = fopen('php://output', 'w');
                            $user = Auth::user();
                            $barangay = $user->barangay_id ? Barangay::find($user->barangay_id) : null;
                            $barangayName = $barangay ? $barangay->name : 'All Barangays';
                            $province = config('app.province', 'DAVAO DEL NORTE');
                            $municipality = config('app.municipality', 'CARMEN');
                            $dateTime = now()->format('F d, Y h:i A');
                            
                            // Add UTF-8 BOM for Excel compatibility
                            fprintf($csv, chr(0xEF).chr(0xBB).chr(0xBF));
                            
                            // Add header rows (matching xlsx format)
                            fputcsv($csv, ['REPUBLIC OF THE PHILIPPINES', '', '', '', '', '', '', '', '', '', '', '', '', '']);
                            fputcsv($csv, ['PROVINCE OF ' . strtoupper($province), '', '', '', '', '', '', '', '', '', '', '', '', '']);
                            fputcsv($csv, ['MUNICIPAL HEALTH OFFICE', '', '', '', '', '', '', '', '', '', '', '', '', '']);
                            fputcsv($csv, ['MUNICIPALITY OF ' . strtoupper($municipality), '', '', '', '', '', '', '', '', '', '', '', '', '']);
                            fputcsv($csv, ['BARANGAY ' . strtoupper($barangayName), '', '', '', '', '', '', '', '', '', '', '', '', '']);
                            fputcsv($csv, [strtoupper($title), '', '', '', '', '', '', '', '', '', '', '', '', '']);
                            fputcsv($csv, ['', '', '', '', '', '', '', '', '', '', '', '', '', '']); // Empty row
                            fputcsv($csv, ['As of : ' . $dateTime, '', '', '', '', '', '', '', '', '', '', '', '', '']);

                            // Column headers
                            fputcsv($csv, [
                                'Full Name', 
                                'Birthdate', 
                                'Age', 
                                'Barangay', 
                                'Category', 
                                'Blood Pressure', 
                                'Sugar Level', 
                                'Contact Number', 
                                'Gender', 
                                'Height', 
                                'Weight', 
                                'BMI', 
                                'Maintenance'
                            ]);
                            
                            foreach ($patients as $patient) {
                                fputcsv($csv, [
                                    trim($patient->first_name . ' ' . ($patient->middle_name ?? '') . ' ' . $patient->last_name),
                                    $patient->birth_date ? $patient->birth_date->format('M d, Y') : 'N/A',
                                    $patient->age ?? 'N/A',
                                    $patient->barangay->name ?? 'N/A',
                                    $patient->category->name ?? 'N/A',
                                    $patient->blood_pressure ?? 'N/A',
                                    $patient->sugar_level ?? 'N/A',
                                    $patient->contact_number ?? 'N/A',
                                    $patient->sex ?? 'N/A',
                                    $patient->height ?? 'N/A',
                                    $patient->weight ?? 'N/A',
                                    $patient->bmi ?? 'N/A',
                                    is_array($patient->medication_maintenance) ? implode(', ', $patient->medication_maintenance) : ($patient->medication_maintenance ?? 'N/A'),
                                ]);
                            }
                            
                            // Footer row
                            fputcsv($csv, ['', '', '', '', '', '', '', '', '', '', '', '', '', '']); // Empty row
                            fputcsv($csv, ['Total Records: ' . count($patients), '', '', '', '', '', '', '', '', '', '', '', '', '']);
                            
                            fclose($csv);
                        }, $reportTitle . '_' . date('Y-m-d_His') . '.csv');
                    }),
            ])
            ->columns([
                TextColumn::make('full_name')
                    ->label('Full Name')
                    ->searchable(['first_name', 'last_name', 'middle_name', 'suffix'])
                    ->getStateUsing(function ($record) {
                        return $record->first_name . ' ' . $record->middle_name . ' ' . $record->last_name . ' ' . $record->suffix;
                    })
                    ->sortable(),
                TextColumn::make('sex')
                    ->formatStateUsing(fn ($state) => SexEnum::tryFrom($state)?->getLabel() ?? ucfirst($state))
                    ->badge()
                    ->color(fn (string $state): string => SexEnum::tryFrom($state)?->getColor() ?? 'gray'),
                TextColumn::make('age')
                    ->getStateUsing(function ($record) {
                        if (!$record->birth_date) {
                            return null;
                        }
                        
                        $birthDate = Carbon::parse($record->birth_date);
                        $now = Carbon::now();
                        
                        $years = (int) $birthDate->diffInYears($now);
                        $months = (int) $birthDate->copy()->addYears($years)->diffInMonths($now);
                        
                        $parts = [];
                        if ($years > 0) {
                            $parts[] = $years . ' ' . ($years === 1 ? 'year' : 'years');
                        }
                        if ($months > 0) {
                            $parts[] = $months . ' ' . ($months === 1 ? 'month' : 'months');
                        }
                        
                        return !empty($parts) ? implode(' ', $parts) : '0 months';
                    })
                    ->label('Age'),
                TextColumn::make('barangay.name')
                    ->label('Barangay')
                    ->searchable()
                    ->sortable()
                    ->visible(fn () => in_array(self::currentUser()->role, [RoleEnum::MHO, RoleEnum::ADMIN])),
                TextColumn::make('purok.name')
                    ->label('Purok')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ?? 'N/A'),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                // Add column who imported the resident/patient this column only visible to MHO and admin
                TextColumn::make('user.name')
                    ->label('Imported By')
                    ->searchable()
                    ->sortable()
                    ->hidden(fn ($record) => ! in_array(self::currentUser()->role, [RoleEnum::MHO, RoleEnum::ADMIN]))
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
                        'Male' => 'Male',
                        'Female' => 'Female',
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
                        ->hidden(fn ($record) => $record->account_user_id)
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

                            $record->account_user_id = $user->id;
                            $record->save();
                            
                            // Send email verification notification
                            $user->sendEmailVerificationNotification();
                        
                            Notification::make()
                                ->title('User created successfully')
                                ->body('A verification email has been sent to the user. They must verify their email address before accessing their account.')
                                ->success()
                                ->send();
                        })
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('assignCategory')
                        ->label('Assign Category')
                        ->icon('heroicon-o-tag')
                        ->color('info')
                        ->form([
                            Select::make('category_id')
                                ->label('Category')
                                ->searchable()
                                ->options(
                                    Category::query()
                                        ->get()
                                        ->mapWithKeys(fn ($category) => [
                                            $category->id => $category->description 
                                                ? "{$category->name} - {$category->description}"
                                                : $category->name
                                        ])
                                        ->toArray()
                                )
                                ->required()
                                ->preload(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $count = $records->count();
                            $records->each->update(['category_id' => $data['category_id']]);
                            
                            Notification::make()
                                ->title('Category assigned successfully')
                                ->body("Category has been assigned to {$count} resident(s).")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->modalHeading('Assign Category to Selected Residents')
                        ->modalDescription('Choose a category to assign to all selected residents.')
                        ->modalSubmitActionLabel('Assign Category'),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultPaginationPageOption(25) // Reduce from 50/100
            ->defaultSort('created_at', 'desc')
            ->deferLoading() // Defer table loading
            ->persistFiltersInSession() // Cache filters
            ->persistSearchInSession()
            ->modifyQueryUsing(function (Builder $query) {
                $user = self::currentUser();
                if (!in_array($user->role, [RoleEnum::BHW, RoleEnum::MIDWIFE])) {
                    return;
                }
                // BHW/Midwife: no barangay_id = see no residents; with barangay_id = only their barangay
                if (is_null($user->barangay_id)) {
                    $query->whereRaw('1 = 0');
                    return;
                }
                $query->where('barangay_id', $user->barangay_id);
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
            'index' => Pages\IndexPatients::route('/'),
            'all' => Pages\AllPatients::route('/all'),
            'list' => Pages\ListPatients::route('/list'),
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
        
        $heightInMeters = $height / 100;
        
        $bmi = $weight / ($heightInMeters * $heightInMeters);
        
        return round($bmi, 2);
    }

    // initialize auth user
    public static function currentUser(): ?User
    {
        return Auth::user();
    }

    /**
     * Get category ID based on age. Uses categories' age_min/age_max (dynamic).
     */
    protected static function getCategoryIdByAge(int $age): ?int
    {
        $category = Category::findByAge($age);
        return $category?->id;
    }

    /**
     * Search for matching patients based on name fields.
     * Returns up to 15 most relevant matches.
     */
    public static function searchMatchingPatients(?string $firstName = null, ?string $middleName = null, ?string $lastName = null): \Illuminate\Database\Eloquent\Collection
    {
        // Only search if at least one name field has at least 2 characters
        $hasSearchableInput = false;
        $searchTerms = [];
        
        if (!empty($firstName) && strlen(trim($firstName)) >= 2) {
            $hasSearchableInput = true;
            $searchTerms['first_name'] = trim($firstName);
        }
        
        if (!empty($middleName) && strlen(trim($middleName)) >= 2) {
            $hasSearchableInput = true;
            $searchTerms['middle_name'] = trim($middleName);
        }
        
        if (!empty($lastName) && strlen(trim($lastName)) >= 2) {
            $hasSearchableInput = true;
            $searchTerms['last_name'] = trim($lastName);
        }
        
        if (!$hasSearchableInput) {
            return collect();
        }
        
        $query = Patient::query()
            ->select('id', 'first_name', 'middle_name', 'last_name', 'suffix', 'birth_date', 'barangay_id')
            ->with('barangay:id,name');
        
        // Apply user permission filtering (barangay scope) for BHW/Midwife
        $user = self::currentUser();
        if ($user && in_array($user->role, [RoleEnum::BHW, RoleEnum::MIDWIFE])) {
            if (is_null($user->barangay_id)) {
                return collect();
            }
            $query->where('barangay_id', $user->barangay_id);
        }
        
        // Build search query with OR conditions across name fields
        $query->where(function ($q) use ($searchTerms) {
            foreach ($searchTerms as $field => $value) {
                $q->orWhere($field, 'LIKE', '%' . $value . '%');
            }
        });
        
        // Order by relevance: prioritize matches where multiple fields match
        // Count how many fields match for each patient
        $matchCountSql = '';
        $bindings = [];
        foreach ($searchTerms as $field => $value) {
            if (!empty($matchCountSql)) {
                $matchCountSql .= ' + ';
            }
            $matchCountSql .= "CASE WHEN {$field} LIKE ? THEN 1 ELSE 0 END";
            $bindings[] = '%' . $value . '%';
        }
        
        if (!empty($matchCountSql)) {
            $query->orderByRaw("({$matchCountSql}) DESC", $bindings);
        }
        
        // Secondary ordering by last name, then first name
        $query->orderBy('last_name', 'asc')
            ->orderBy('first_name', 'asc')
            ->limit(15);
        
        return $query->get();
    }
}