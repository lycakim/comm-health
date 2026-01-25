<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Purok;
use App\Enums\SexEnum;
use App\Models\Patient;
use App\Models\Program;
use Filament\Forms\Get;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\Referral;
use App\Models\Consultation;
use App\Enums\CivilStatusEnum;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use App\Enums\EducationalAttainmentEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\CheckboxList;
use App\Services\PatientFormOptionsServices;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use App\Services\ConsultationFormOptionServices;
use App\Models\Laboratory;
use App\Enums\RoleEnum;

class ConsultationFormService
{
    public static function handle()
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
        return [
            Grid::make(1)
                ->schema([
                    Wizard::make([
                        Wizard\Step::make('Consultation Step 1')
                            ->description('Patient Information')
                            ->schema([
                                // PATIENT SELECTION
                                Section::make()
                                    ->schema([
                                        Select::make('patient_id')
                                            ->label('Patient')
                                            ->reactive()
                                            ->live()
                                            ->options(function () {
                                                $user = Auth::user();
                                                $query = Patient::query();
                                                
                                                // Filter by barangay for BHW users
                                                if ($user && $user->role == RoleEnum::BHW && $user->barangay_id) {
                                                    $query->where('barangay_id', $user->barangay_id);
                                                }
                                                
                                                return $query->get()
                                                    ->mapWithKeys(function ($patient) {
                                                        return [$patient->id => $patient->first_name . ' ' . $patient->last_name];
                                                    })
                                                    ->sort()
                                                    ->toArray();
                                            })
                                            ->preload()
                                            ->searchable()
                                            ->disabledOn('edit')
                                            ->required(),
                                        Select::make('program_id')
                                            ->label('Program')
                                            ->reactive()
                                            ->live()
                                            ->options(function () {
                                                return Program::query()
                                                    ->get()
                                                    ->mapWithKeys(function ($patient) {
                                                        return [$patient->id => $patient->name];
                                                    })
                                                    ->sort()
                                                    ->toArray();
                                            })
                                            ->preload()
                                            ->searchable()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                $set('report_field_response', []);
                                            })
                                            ->disabledOn('edit'),
                                        DateTimePicker::make('date')
                                            ->default(function () {
                                                return Carbon::now('Asia/Manila')->format('Y-m-d H:i:s');
                                            })
                                            ->disabledOn('edit')
                                            ->required(),
                                    ])->columns(3),
                                
                                // PATIENT INFORMATION PREVIEW
                                Section::make('Patient Information')
                                    ->collapsible()
                                    ->schema([
                                        Fieldset::make('Patient')
                                            ->schema([
                                                Placeholder::make('patient_name')
                                                    ->label('Patient Name')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';
                                                        
                                                        $patient = Patient::find($patientId);
                                                        return $patient ? $patient->first_name . ' ' . $patient->middle_name . ' ' . $patient->last_name : '-';
                                                    }),
                                                Placeholder::make('gender')
                                                    ->label('Gender')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';

                                                        $patient = Patient::find($patientId);
                                                        if (!$patient) return '-';

                                                        $enum = SexEnum::tryFrom($patient->sex);
                                                        return $enum ? $enum->getLabel() : '-';
                                                    }),
                                                Placeholder::make('civil_status')
                                                    ->label('Civil Status')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';

                                                        $patient = Patient::find($patientId);
                                                        if (!$patient) return '-';

                                                        return $patient->civil_status ?? '-';
                                                    }),
                                                Placeholder::make('age')
                                                    ->label('Age')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';
                                                        
                                                        $patient = Patient::find($patientId);
                                                        return $patient ? $patient->age : '-';
                                                    }),
                                                Placeholder::make('educational_attainment')
                                                    ->visible(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return false;
                                                        
                                                        $patient = Patient::with('category')->find($patientId);
                                                        return $patient ? $patient->educational_attainment : false;
                                                    })
                                                    ->label('Educational Attainment')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';

                                                        $patient = Patient::find($patientId);
                                                        if (!$patient) return '-';

                                                        return $patient->educational_attainment ?? '-';
                                                    }),
                                                Placeholder::make('birth_date')
                                                    ->label('Date of Birth')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';
                                                        
                                                        $patient = Patient::find($patientId);
                                                        return $patient ? Carbon::parse($patient->birth_date)->format('M d, Y') : '-';
                                                    }),
                                            ])
                                            ->columns(3),
                                        Fieldset::make('Medical History')
                                            ->schema([
                                                Placeholder::make('blood_pressure')
                                                    ->label('Blood Pressure (mm Hg)')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';
                                                        
                                                        $patient = Patient::find($patientId);
                                                        return $patient ? $patient->blood_pressure : '-';
                                                    }),
                                                Placeholder::make('sugar_level')
                                                    ->label('Sugar Level (mm/dl)')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';
                                                        
                                                        $patient = Patient::find($patientId);
                                                        return $patient ? $patient->sugar_level : '-';
                                                    }),
                                                Placeholder::make('height')
                                                    ->label('Height (cm)')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';
                                                        
                                                        $patient = Patient::find($patientId);
                                                        return $patient ? $patient->height : '-';
                                                    }),
                                                Placeholder::make('weight')
                                                    ->label('Weight (kg)')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';
                                                        
                                                        $patient = Patient::find($patientId);
                                                        return $patient ? $patient->weight : '-';
                                                    }),
                                                Placeholder::make('bmi')
                                                    ->label('BMI')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';
                                                        
                                                        $patient = Patient::find($patientId);
                                                        return $patient ? $patient->bmi : '-';
                                                    }),
                                                Placeholder::make('bmi_category')
                                                    ->label('BMI Category')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';
                                                        
                                                        $patient = Patient::find($patientId);
                                                        return $patient ? $patient->bmi_category : '-';
                                                    }),
                                                Placeholder::make('health_statuses')
                                                    ->label('Health Statuses')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';

                                                        $patient = Patient::find($patientId);
                                                        if (!$patient) return '-';

                                                        $statuses = $patient->health_statuses;
                                                        $statusesArray = is_string($statuses) ? explode(',', $statuses) : (array) $statuses;
                                                        $statusesArray = array_filter(array_map('trim', $statusesArray));

                                                        return implode(', ', $statusesArray);
                                                    }),
                                                Placeholder::make('medication_maintenance')
                                                    ->label('Medication/Maintenance')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';

                                                        $patient = Patient::find($patientId);
                                                        if (!$patient) return '-';

                                                        $meds = $patient->medication_maintenance;
                                                        $medsArray = is_string($meds) ? explode(',', $meds) : (array) $meds;
                                                        $medsArray = array_filter(array_map('trim', $medsArray));

                                                        return implode(', ', $medsArray);
                                                    }),
                                                Placeholder::make('category')
                                                    ->label('Category')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';

                                                        $patient = Patient::find($patientId);
                                                        if (!$patient) return '-';

                                                        return $patient->category ? $patient->category->name : '-';
                                                    }),
                                            ])
                                            ->columns(3)
                                    ])->columns(3)
                                    ->visible(fn (Get $get) => (bool) $get('patient_id'))
                                    ->headerActions([
                                        Action::make('editPatient')
                                            ->label('Edit Patient Info')
                                            ->color('gray')
                                            ->icon('heroicon-o-pencil-square')
                                            ->modalHeading('Update Patient Information')
                                            ->fillForm(function (Get $get) {
                                                $patientId = $get('patient_id');
                                                $patient = Patient::find($patientId);

                                                return $patient ? $patient->only([
                                                    'first_name',
                                                    'middle_name',
                                                    'last_name',
                                                    'sex',
                                                    'civil_status',
                                                    'birth_date',
                                                    'educational_attainment',
                                                    'blood_pressure',
                                                    'sugar_level',
                                                    'height',
                                                    'weight',
                                                    'bmi',
                                                    'bmi_category',
                                                    'health_statuses',
                                                    'medication_maintenance',
                                                    'category_id',
                                                ]) : [];
                                            })
                                            ->form([
                                                Fieldset::make('Patient Information')
                                                    ->schema([
                                                        TextInput::make('first_name')->label('First Name')->required(),
                                                        TextInput::make('last_name')->label('Last Name')->required(),
                                                        TextInput::make('middle_name')->label('Middle Name'),
                                                        Select::make('sex')
                                                            ->label('Gender')
                                                            ->searchable()
                                                            ->options([
                                                                'male' => 'Male',
                                                                'female' => 'Female',
                                                            ])
                                                            ->required(),
                                                        Select::make('civil_status')
                                                            ->label('Civil Status')
                                                            ->searchable()
                                                            ->options(PatientFormOptionsServices::getPatientStatus())
                                                            ->required(),
                                                        DatePicker::make('birth_date')->label('Date of Birth')->required(),
                                                        Select::make('educational_attainment')
                                                            ->label('Educational Attainment')
                                                            ->columnSpanFull()
                                                            ->searchable()
                                                            ->preload()
                                                            ->options(fn() => collect(PatientFormOptionsServices::getPatientEducationalAttainment())->sort()->toArray())
                                                    ])
                                                    ->columns(2),
                                                Fieldset::make('Medical Information')
                                                    ->schema([
                                                        Select::make('category_id')
                                                            ->label('Category')
                                                            ->searchable()
                                                            ->columnSpanFull()
                                                            ->options(Category::query()->get()->pluck('name', 'id')->sort()->toArray())
                                                            ->required(),
                                                        TextInput::make('blood_pressure')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->label('Blood Pressure')
                                                            ->hint('mm Hg'),
                                                        TextInput::make('sugar_level')
                                                            ->label('Sugar Level')
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
                                                            ->hint('kg')
                                                            ->numeric()
                                                            ->label('Weight')
                                                            ->reactive()
                                                            ->afterStateUpdated($calculateBMI),
                                                        TextInput::make('bmi')->label('BMI'),
                                                        TextInput::make('bmi_category')->label('BMI Category'),
                                                        CheckboxList::make('health_statuses')
                                                            ->label('Health Statuses')
                                                            ->columns(3)
                                                            ->columnSpanFull()
                                                            ->gridDirection('row')
                                                            ->options(fn () => PatientFormOptionsServices::getPatientHealthStatuses()),
                                                        CheckboxList::make('medication_maintenance')
                                                            ->columns(3)
                                                            ->columnSpanFull()
                                                            ->gridDirection('row')
                                                            ->options(fn () => PatientFormOptionsServices::getMedicationMaintenance()),
                                                    ])
                                            ])
                                            ->action(function (array $data, Get $get) {
                                                $patientId = $get('patient_id');
                                                $patient = Patient::find($patientId);

                                                if (!$patient) {
                                                    Notification::make()->title('Patient not found.')->danger()->send();
                                                    return;
                                                }

                                                $patient->update($data);

                                                Notification::make()
                                                    ->title('Patient information updated successfully!')
                                                    ->success()
                                                    ->send();
                                            }),
                                    ]),
                            ]),
                            
                        Wizard\Step::make('Consultation Step 2')
                            ->description('Additional consultation details')
                            ->schema([
                                // PATIENT INFORMATION PREVIEW
                                Section::make('Patient Information')
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Fieldset::make('Patient')
                                            ->schema([
                                                Placeholder::make('patient_name')
                                                    ->label('Patient Name')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';
                                                        
                                                        $patient = Patient::find($patientId);
                                                        return $patient ? $patient->first_name . ' ' . $patient->middle_name . ' ' . $patient->last_name : '-';
                                                    }),
                                                Placeholder::make('gender')
                                                    ->label('Gender')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';

                                                        $patient = Patient::find($patientId);
                                                        if (!$patient) return '-';

                                                        $enum = SexEnum::tryFrom($patient->sex);
                                                        return $enum ? $enum->getLabel() : '-';
                                                    }),
                                                Placeholder::make('civil_status')
                                                    ->label('Civil Status')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';

                                                        $patient = Patient::find($patientId);
                                                        if (!$patient) return '-';

                                                        return $patient->civil_status ?? '-';
                                                    }),
                                                Placeholder::make('age')
                                                    ->label('Age')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';
                                                        
                                                        $patient = Patient::find($patientId);
                                                        return $patient ? $patient->age : '-';
                                                    }),
                                                Placeholder::make('educational_attainment')
                                                    ->visible(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return false;
                                                        
                                                        $patient = Patient::with('category')->find($patientId);
                                                        return $patient ? $patient->educational_attainment : false;
                                                    })
                                                    ->label('Educational Attainment')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';

                                                        $patient = Patient::find($patientId);
                                                        if (!$patient) return '-';

                                                        return $patient->educational_attainment ?? '-';
                                                    }),
                                                Placeholder::make('birth_date')
                                                    ->label('Date of Birth')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';
                                                        
                                                        $patient = Patient::find($patientId);
                                                        return $patient ? Carbon::parse($patient->birth_date)->format('M d, Y') : '-';
                                                    }),
                                            ])
                                            ->columns(3),
                                        Fieldset::make('Medical History')
                                            ->schema([
                                                Placeholder::make('blood_pressure')
                                                    ->label('Blood Pressure (mm Hg)')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';
                                                        
                                                        $patient = Patient::find($patientId);
                                                        return $patient ? $patient->blood_pressure : '-';
                                                    }),
                                                Placeholder::make('sugar_level')
                                                    ->label('Sugar Level (mm/dl)')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';
                                                        
                                                        $patient = Patient::find($patientId);
                                                        return $patient ? $patient->sugar_level : '-';
                                                    }),
                                                Placeholder::make('height')
                                                    ->label('Height (cm)')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';
                                                        
                                                        $patient = Patient::find($patientId);
                                                        return $patient ? $patient->height : '-';
                                                    }),
                                                Placeholder::make('weight')
                                                    ->label('Weight (kg)')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';
                                                        
                                                        $patient = Patient::find($patientId);
                                                        return $patient ? $patient->weight : '-';
                                                    }),
                                                Placeholder::make('bmi')
                                                    ->label('BMI')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';
                                                        
                                                        $patient = Patient::find($patientId);
                                                        return $patient ? $patient->bmi : '-';
                                                    }),
                                                Placeholder::make('bmi_category')
                                                    ->label('BMI Category')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';
                                                        
                                                        $patient = Patient::find($patientId);
                                                        return $patient ? $patient->bmi_category : '-';
                                                    }),
                                                Placeholder::make('health_statuses')
                                                    ->label('Health Statuses')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';

                                                        $patient = Patient::find($patientId);
                                                        if (!$patient) return '-';

                                                        $statuses = $patient->health_statuses;
                                                        $statusesArray = is_string($statuses) ? explode(',', $statuses) : (array) $statuses;
                                                        $statusesArray = array_filter(array_map('trim', $statusesArray));

                                                        return implode(', ', $statusesArray);
                                                    }),
                                                Placeholder::make('medication_maintenance')
                                                    ->label('Medication/Maintenance')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';

                                                        $patient = Patient::find($patientId);
                                                        if (!$patient) return '-';

                                                        $meds = $patient->medication_maintenance;
                                                        $medsArray = is_string($meds) ? explode(',', $meds) : (array) $meds;
                                                        $medsArray = array_filter(array_map('trim', $medsArray));

                                                        return implode(', ', $medsArray);
                                                    }),
                                                Placeholder::make('category')
                                                    ->label('Category')
                                                    ->content(function (Get $get) {
                                                        $patientId = $get('patient_id');
                                                        if (!$patientId) return '-';

                                                        $patient = Patient::find($patientId);
                                                        if (!$patient) return '-';

                                                        return $patient->category ? $patient->category->name : '-';
                                                    }),
                                            ])
                                            ->columns(3)
                                    ])->columns(3)
                                    ->visible(fn (Get $get) => (bool) $get('patient_id'))
                                    ->headerActions([
                                        Action::make('editPatient')
                                            ->label('Edit Patient Info')
                                            ->color('gray')
                                            ->icon('heroicon-o-pencil-square')
                                            ->modalHeading('Update Patient Information')
                                            ->fillForm(function (Get $get) {
                                                $patientId = $get('patient_id');
                                                $patient = Patient::find($patientId);

                                                return $patient ? $patient->only([
                                                    'first_name',
                                                    'middle_name',
                                                    'last_name',
                                                    'sex',
                                                    'civil_status',
                                                    'birth_date',
                                                    'educational_attainment',
                                                    'blood_pressure',
                                                    'sugar_level',
                                                    'height',
                                                    'weight',
                                                    'bmi',
                                                    'bmi_category',
                                                    'health_statuses',
                                                    'medication_maintenance',
                                                    'category_id',
                                                ]) : [];
                                            })
                                            ->form([
                                                Fieldset::make('Patient Information')
                                                    ->schema([
                                                        TextInput::make('first_name')->label('First Name')->required(),
                                                        TextInput::make('last_name')->label('Last Name')->required(),
                                                        TextInput::make('middle_name')->label('Middle Name'),
                                                        Select::make('sex')
                                                            ->label('Gender')
                                                            ->searchable()
                                                            ->options([
                                                                'male' => 'Male',
                                                                'female' => 'Female',
                                                            ])
                                                            ->required(),
                                                        Select::make('civil_status')
                                                            ->label('Civil Status')
                                                            ->searchable()
                                                            ->options(PatientFormOptionsServices::getPatientStatus())
                                                            ->required(),
                                                        DatePicker::make('birth_date')->label('Date of Birth')->required(),
                                                        Select::make('educational_attainment')
                                                            ->label('Educational Attainment')
                                                            ->columnSpanFull()
                                                            ->searchable()
                                                            ->preload()
                                                            ->options(fn() => collect(PatientFormOptionsServices::getPatientEducationalAttainment())->sort()->toArray())
                                                    ])
                                                    ->columns(2),
                                                Fieldset::make('Medical Information')
                                                    ->schema([
                                                        Select::make('category_id')
                                                            ->label('Category')
                                                            ->searchable()
                                                            ->columnSpanFull()
                                                            ->options(Category::query()->get()->pluck('name', 'id')->sort()->toArray())
                                                            ->required(),
                                                        TextInput::make('blood_pressure')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->label('Blood Pressure')
                                                            ->hint('mm Hg'),
                                                        TextInput::make('sugar_level')
                                                            ->label('Sugar Level')
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
                                                            ->hint('kg')
                                                            ->numeric()
                                                            ->label('Weight')
                                                            ->reactive()
                                                            ->afterStateUpdated($calculateBMI),
                                                        TextInput::make('bmi')->label('BMI'),
                                                        TextInput::make('bmi_category')->label('BMI Category'),
                                                        CheckboxList::make('health_statuses')
                                                            ->label('Health Statuses')
                                                            ->columns(3)
                                                            ->columnSpanFull()
                                                            ->gridDirection('row')
                                                            ->options(fn () => PatientFormOptionsServices::getPatientHealthStatuses()),
                                                        CheckboxList::make('medication_maintenance')
                                                            ->columns(3)
                                                            ->columnSpanFull()
                                                            ->gridDirection('row')
                                                            ->options(fn () => PatientFormOptionsServices::getMedicationMaintenance()),
                                                    ])
                                            ])
                                            ->action(function (array $data, Get $get) {
                                                $patientId = $get('patient_id');
                                                $patient = Patient::find($patientId);

                                                if (!$patient) {
                                                    Notification::make()->title('Patient not found.')->danger()->send();
                                                    return;
                                                }

                                                $patient->update($data);

                                                Notification::make()
                                                    ->title('Patient information updated successfully!')
                                                    ->success()
                                                    ->send();
                                            }),
                                    ]),

                                Section::make('Patient Consultation Details')
                                    ->collapsible()
                                    ->schema([
                                        Fieldset::make('Program Report Fields')
                                            ->schema(function (callable $get, $record) {
                                                // Get program_id from form state or record
                                                $programId = $get('program_id') ?? $record?->program_id;
                                                
                                                if (!$programId) {
                                                    return [
                                                        Placeholder::make('select_program')
                                                            ->label('')
                                                            ->content('Please select a program first to view report fields.')
                                                    ];
                                                }
                                                
                                                // Get the program with report_field
                                                $program = \App\Models\Program::find($programId);
                                                
                                                if (!$program || empty($program->report_field)) {
                                                    return [
                                                        Placeholder::make('no_fields')
                                                            ->label('')
                                                            ->content('This program has no report fields configured.')
                                                    ];
                                                }
                                                
                                                $reportFields = is_string($program->report_field) 
                                                    ? json_decode($program->report_field, true) 
                                                    : $program->report_field;
                                                
                                                return collect($reportFields)->map(function ($field) {
                                                    $component = match($field['type']) {
                                                        'textarea' => Textarea::make('report_field_response.' . $field['name'])
                                                            ->label($field['label'])
                                                            ->rows($field['rows'] ?? 4),
                                                        'text' => TextInput::make('report_field_response.' . $field['name'])
                                                            ->label($field['label'])
                                                            ->placeholder($field['placeholder'] ?? ''),
                                                        'number' => TextInput::make('report_field_response.' . $field['name'])
                                                            ->label($field['label'])
                                                            ->numeric()
                                                            ->placeholder($field['placeholder'] ?? ''),
                                                        'select' => Select::make('report_field_response.' . $field['name'])
                                                            ->label($field['label'])
                                                            ->options($field['options'] ?? []),
                                                        'radio' => Radio::make('report_field_response.' . $field['name'])
                                                            ->label($field['label'])
                                                            ->options($field['options'] ?? []),
                                                        'checkbox' => Checkbox::make('report_field_response.' . $field['name'])
                                                            ->label($field['label']),
                                                        'toggle' => Toggle::make('report_field_response.' . $field['name'])
                                                            ->label($field['label']),
                                                        'date' => DatePicker::make('report_field_response.' . $field['name'])
                                                            ->label($field['label']),
                                                        'time' => TimePicker::make('report_field_response.' . $field['name'])
                                                            ->label($field['label']),
                                                        'datetime' => DateTimePicker::make('report_field_response.' . $field['name'])
                                                            ->label($field['label']),
                                                        default => TextInput::make('report_field_response.' . $field['name'])
                                                            ->label($field['label'])
                                                    };
                                                    
                                                    // Apply common properties
                                                    if (!empty($field['helper_text'])) {
                                                        $component->helperText($field['helper_text']);
                                                    }
                                                    
                                                    if (!empty($field['required'])) {
                                                        $component->required();
                                                    }
                                                    
                                                    if (!empty($field['placeholder']) && method_exists($component, 'placeholder')) {
                                                        $component->placeholder($field['placeholder']);
                                                    }
                                                    
                                                    return $component;
                                                })->toArray();
                                            })
                                            ->columns(3)
                                            ->visible(fn (callable $get) => $get('program_id') !== null),
                                        Fieldset::make()
                                            ->schema([
                                                ToggleButtons::make('make_referral')
                                                    ->label('Make a referral?')
                                                    ->boolean()
                                                    ->inline()
                                                    ->default(false)
                                                    ->dehydrated(false)
                                                    ->reactive()
                                                    ->live(),
                                                ToggleButtons::make('disability')
                                                    ->label('With Disability?')
                                                    ->boolean()
                                                    ->reactive()
                                                    ->required()
                                                    ->inline(),
                                                ToggleButtons::make('philhealth')
                                                    ->label('With Philhealth?')
                                                    ->boolean()
                                                    ->inline(),
                                                ToggleButtons::make('member_of_4ps')
                                                    ->label('4Ps member?')
                                                    ->boolean()
                                                    ->inline(),
                                                ToggleButtons::make('nhts_member')
                                                    ->label('NHTS Member?')
                                                    ->boolean()
                                                    ->inline(),
                                                ToggleButtons::make('birth_plan')
                                                    ->label('Birth planned?')
                                                    ->boolean()
                                                    ->inline(),
                                                // ToggleButtons::make('type')
                                                //     ->inline()
                                                //     ->inlineLabel(false)
                                                //     ->options(fn () => ConsultationFormOptionServices::getTypeOptions()),
                                            ])
                                            ->columns(3),
                                        Fieldset::make('Mother Information')
                                            ->schema([
                                                TextInput::make('mother_first_name')
                                                    ->label('First name'),
                                                TextInput::make('mother_last_name')
                                                    ->label('Last Name'),
                                                TextInput::make('mother_middle_name')
                                                    ->label('Middle Name'),
                                                Textarea::make('maternal_notes')
                                                    ->columnSpanFull(),
                                            ])
                                            ->visible(function (Get $get) {
                                                $patientId = $get('patient_id');
                                                if (!$patientId) return false;
                                                
                                                $patient = Patient::with('category')->find($patientId);
                                                if (!$patient->category) return false;

                                                return $patient->category->is_child;
                                            })
                                            ->columns(3),
                                        Fieldset::make('Maternal Information for Husband/Partner')
                                            ->schema([
                                                TextInput::make('husband_first_name')
                                                    ->label('Husband/Partner First name'),
                                                TextInput::make('husband_last_name')
                                                    ->label('Husband/Partner Last Name'),
                                                TextInput::make('husband_middle_name')
                                                    ->label('Husband/Partner Middle Name'),
                                                TextInput::make('husband_contact_no')
                                                    ->label('Husband/Partner Contact No'),
                                                TextInput::make('husband_occupation')
                                                    ->label('Husband/Partner Occupation'),
                                                ToggleButtons::make('husband_philhealth')
                                                    ->label('With Philhealth?')
                                                    ->boolean()
                                                    ->inline(),
                                                ToggleButtons::make('husband_member_of_4ps')
                                                    ->label('4ps member?')
                                                    ->boolean()
                                                    ->inline(),
                                                ToggleButtons::make('husband_nhts_member')
                                                    ->label('NHTS Member?')
                                                    ->boolean()
                                                    ->inline(),
                                                
                                            ])
                                            ->visible(function (Get $get) {
                                                $patientId = $get('patient_id');
                                                if (!$patientId) return false;
                                                
                                                $patient = Patient::with('category')->find($patientId);
                                                if (!$patient->category) return false;

                                                return $patient->category->is_maternal;
                                            })
                                            ->columns(3),
                                        Fieldset::make('Maternal Information for Mother')
                                            ->schema([
                                                DatePicker::make('mother_lmp_date')
                                                    ->helperText('Last Menstrual Period')
                                                    ->label('LMP Date'),
                                                TextInput::make('child_order')
                                                    ->label('Child Order')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->maxValue(20),
                                                TextInput::make('delivery_address')
                                                    ->label('Where the child was delivered?'),
                                                DatePicker::make('mother_edc_date')
                                                    ->helperText('Estimated Date of Confinement')
                                                    ->label('EDC Date'),
                                                ToggleButtons::make('mother_and_child_book')
                                                    ->label('Mother and Child Book')
                                                    ->boolean()
                                                    ->inline(),
                                                TextInput::make('number_of_pregnancies')
                                                    ->label('Number of Pregnancies')
                                                    ->helperText('Gravida')
                                                    ->numeric(),
                                                TextInput::make('successful_deliveries')
                                                    ->label('Successful Deliveries')
                                                    ->helperText('Para')
                                                    ->numeric(),
                                                TextInput::make('pregnancy_losses')
                                                    ->label('Pregnancy Losses')
                                                    ->helperText('Abortus')
                                                    ->numeric(),
                                                ToggleButtons::make('birth_plan')
                                                    ->label('Birth Plan')
                                                    ->boolean()
                                                    ->inline(),
                                            ])
                                            ->visible(function (Get $get) {
                                                $patientId = $get('patient_id');
                                                if (!$patientId) return false;
                                                
                                                $patient = Patient::with('category')->find($patientId);
                                                if (!$patient->category) return false;

                                                return $patient->category->is_maternal;
                                            })
                                            ->columns(3),
                                        Fieldset::make('GPA')
                                            ->schema([
                                                TextInput::make('laboratory_exam')
                                                    ->label('Laboratory Exam'),
                                                TextInput::make('first_hgb')
                                                    ->label('1st HGB'),
                                                TextInput::make('blood_type')
                                                    ->label('Blood Type'),
                                                TextInput::make('iron_imms')
                                                    ->label('Iron/IMMS'),
                                                TextInput::make('iodized_salt')
                                                    ->label('Iodized Salt'),
                                                TextInput::make('second_hgb')
                                                    ->label('2nd HGB'),
                                                TextInput::make('ua')
                                                    ->columnSpanFull()
                                                    ->label('U/A'),
                                            ])
                                            ->visible(function (Get $get) {
                                                $patientId = $get('patient_id');
                                                if (!$patientId) return false;
                                                
                                                $patient = Patient::with('category')->find($patientId);
                                                if (!$patient->category) return false;

                                                return $patient->category->is_maternal;
                                            })
                                            ->columns(3),
                                        Fieldset::make('Laboratory')
                                            ->schema([
                                                DatePicker::make('imm_received_dates')
                                                    ->label('IMM./Received Dates'),
                                                DatePicker::make('tt1_date')
                                                    ->label('TT1 Date'),
                                                DatePicker::make('tt2_date')
                                                    ->label('TT2 Date'),
                                                DatePicker::make('tt3_date')
                                                    ->label('TT3 Date'),
                                                DatePicker::make('tt4_date')
                                                    ->label('TT4 Date'),
                                                DatePicker::make('tt5_date')
                                                    ->label('TT5 Date'),
                                                DatePicker::make('tt_imm')
                                                    ->default(Carbon::now())
                                                    ->label('TT IMM'),
                                            ])
                                            ->visible(function (Get $get) {
                                                $patientId = $get('patient_id');
                                                if (!$patientId) return false;
                                                
                                                $patient = Patient::with('category')->find($patientId);
                                                if (!$patient->category) return false;

                                                return $patient->category->is_maternal;
                                            })
                                            ->columns(2),
                                        Fieldset::make('Previous TT/TD - tetanus toxoid OR tetanus & diphtheria')
                                            ->schema([
                                                TextInput::make('number_of_pregnancies')
                                                    ->label('Number of Pregnancies')
                                                    ->helperText('Gravida')
                                                    ->numeric(),
                                                TextInput::make('successful_deliveries')
                                                    ->label('Successful Deliveries')
                                                    ->helperText('Para')
                                                    ->numeric(),
                                                TextInput::make('pregnancy_losses')
                                                    ->label('Pregnancy Losses')
                                                    ->helperText('Abortus')
                                                    ->numeric(),
                                                ToggleButtons::make('birth_plan')
                                                    ->label('Birth Plan')
                                                    ->boolean()
                                                    ->inline(),
                                            ])
                                            ->visible(function (Get $get) {
                                                $patientId = $get('patient_id');
                                                if (!$patientId) return false;
                                                
                                                $patient = Patient::with('category')->find($patientId);
                                                if (!$patient->category) return false;

                                                return $patient->category->is_maternal;
                                            })
                                            ->columns(2),
                                        Fieldset::make('Other Maternal Labs')
                                            ->schema([
                                                TextInput::make('blood_pressure')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->label('Blood Pressure')
                                                    ->hint('mm Hg'),
                                                TextInput::make('weight')
                                                    ->minValue(0)
                                                    ->columnSpan(2)
                                                    ->hint('kg')
                                                    ->numeric()
                                                    ->label('Weight'),
                                                TextInput::make('height')
                                                    ->minValue(0)
                                                    ->numeric()
                                                    ->hint('cm')
                                                    ->label('Height'),
                                                TextInput::make('fundal_height')
                                                    ->minValue(0)
                                                    ->numeric()
                                                    ->hint('cm')
                                                    ->label('Fundal Height'),
                                                TextInput::make('fetal_hydronephrosis')
                                                    ->minValue(0)
                                                    ->numeric()
                                                    ->hint('cm')
                                                    ->label('Fetal Hydronephrosis'),
                                                TextInput::make('age_of_gestation')
                                                    ->minValue(0)
                                                    ->numeric()
                                                    ->hint('cm')
                                                    ->label('Age of Gestation'),
                                            ])
                                            ->visible(function (Get $get) {
                                                $patientId = $get('patient_id');
                                                if (!$patientId) return false;
                                                
                                                $patient = Patient::with('category')->find($patientId);
                                                if (!$patient->category) return false;

                                                return $patient->category->is_maternal;
                                            })
                                            ->columns(3),
                                        Fieldset::make('Child Information')
                                            ->schema([
                                                TextInput::make('child_weight')
                                                    ->label('Child Weight')
                                                    ->numeric()
                                                    ->hint('kg')
                                                    ->columnSpan(2),
                                                TextInput::make('child_order')
                                                    ->label('Child Order')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->maxValue(20)
                                                    ->columnSpan(2),
                                                ToggleButtons::make('mother_status')
                                                    ->label('Mother\'s Status')  
                                                    ->helperText('TT/TD Status of mother/CPAB')
                                                    ->boolean()
                                                    ->inline(),
                                                ToggleButtons::make('hepa_b')
                                                    ->label('Hepa B')
                                                    ->boolean()
                                                    ->inline(),
                                                ToggleButtons::make('nbs')
                                                    ->label('NBS')
                                                    ->boolean()
                                                    ->inline(),
                                                ToggleButtons::make('prenatal_dates')
                                                    ->label('Add Prenatal Dates')
                                                    ->boolean()
                                                    ->live()
                                                    ->default(false)
                                                    ->inline(),
                                            ])
                                            ->visible(function (Get $get) {
                                                $patientId = $get('patient_id');
                                                if (!$patientId) return false;
                                                
                                                $patient = Patient::with('category')->find($patientId);
                                                if (!$patient->category) return false;

                                                return $patient->category->is_child;
                                            })
                                            ->columns(4),
                                        Fieldset::make('Immunization')
                                            ->visible(function (Get $get) {
                                                $prenatalDates = $get('prenatal_dates');
                                                $patientId = $get('patient_id');
                                                if (!$patientId) return false;
                                                
                                                $patient = Patient::with('category')->find($patientId);
                                                if (!$patient->category) return false;

                                                return $prenatalDates && $patient->category->is_child;
                                            })
                                            ->schema([
                                                DatePicker::make('bcg_date')
                                                    ->label('BCG'),
                                                DatePicker::make('prenatal_date')
                                                    ->label('Prenatal Date'),
                                                DatePicker::make('polio_date')
                                                    ->label('Polio'),
                                                DatePicker::make('ipv_date')
                                                    ->label('IPV'),
                                                DatePicker::make('pcv_date')
                                                    ->label('PCV'),
                                                DatePicker::make('measles_date')
                                                    ->label('Measles'),
                                                DatePicker::make('mmr_date')
                                                    ->label('MMR')
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(3),
                                        Fieldset::make()
                                            ->schema([
                                                CheckboxList::make('disabilities')
                                                    ->required(fn (Get $get) => $get('disability'))
                                                    ->disabled(fn (Get $get) => ! $get('disability'))
                                                    ->columnSpanFull()
                                                    ->gridDirection('row')
                                                    ->columns(2)
                                                    ->options(fn () => ConsultationFormOptionServices::getDisabilitiesOptions()),
                                                TextInput::make('other_diseases')
                                                    ->columnSpanFull(),
                                            ]),
                                        Textarea::make('notes')
                                            ->maxLength(65535)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(3),

                                    Hidden::make('user_id')
                                        ->default(fn () => Auth::user())
                                        ->required()
                            ]),
                        Wizard\Step::make('Consultation Step 3')
                            ->visible(fn (Get $get) => $get('make_referral'))
                            ->description('Make a referral')
                            ->schema([
                                Select::make('referred_to')
                                    ->options([
                                        'Carmen MHO' => 'Carmen MHO',
                                    ])
                                    ->default('Carmen MHO')
                                    ->required(),
                                    
                                Radio::make('referral_reason')
                                    ->options([
                                        'Hospital Capability' => 'Hospital Capability',
                                        'Lack of Specialists' => 'Lack of Specialists',
                                        'Financial Constraint' => 'Financial Constraint',
                                        'Other' => 'Other',
                                    ])
                                    ->columns(2)
                                    ->live()
                                    ->required()
                                    ->gridDirection('row'),

                                Textarea::make('reason_for_referral_other')
                                    ->label('State your reason for referral')
                                    ->hidden(fn (Get $get) => ! $get('referral_reason') || $get('referral_reason') !== 'Other')
                                    ->required(fn (Get $get) => $get('referral_reason') === 'Other')
                                    ->maxLength(65535),
                                    
                                CheckboxList::make('laboratories')
                                    ->hintAction(function () {
                                        return Action::make('add-new-laboratory')
                                            ->icon('heroicon-o-plus')
                                            ->label('Add New Laboratory')
                                            ->form([
                                                TextInput::make('name')
                                                    ->label('Laboratory Name')
                                                    ->required(),
                                            ])
                                            ->action(function (array $data) {
                                                $laboratory = Laboratory::create([
                                                    'name' => $data['name'],
                                                    'user_id' => Auth::id(),
                                                ]);
                                                return $laboratory;
                                            });
                                    })
                                    ->label('Laboratories')
                                    ->searchable()
                                    ->options(function () {
                                        return Laboratory::query()
                                            ->pluck('name', 'id')
                                            ->sort()
                                            ->toArray();
                                    })
                                    ->helperText(function (Get $get) {
                                        // Must inform to add new laboratory if the list is empty
                                        $laboratories = Laboratory::all();
                                        if ($laboratories->isEmpty()) {
                                            return 'No laboratories found. Please add new laboratory.';
                                        }
                                        return null;
                                    })
                                    ->bulkToggleable()
                                    ->columns(2),
                                
                                ToggleButtons::make('urgency')
                                    ->required()
                                    ->options([
                                        'Emergency' => 'Emergency',
                                        'Ambulatory' => 'Ambulatory',
                                        'Medico-Legal' => 'Medico-Legal',
                                    ])
                                    ->live()
                                    ->inline(),
                                
                                ToggleButtons::make('surgical_operation')
                                    ->label('Surgical Operation?')
                                    ->boolean()
                                    ->default(false)
                                    ->live()
                                    ->inline(),
                                
                                TextInput::make('procedure')
                                    ->label('What Procedure?')
                                    ->hidden(fn (Get $get) => ! $get('surgical_operation'))
                                    ->required(fn (Get $get) => $get('surgical_operation')),
                                
                                ToggleButtons::make('drug_allergy')
                                    ->boolean()
                                    ->default(false)
                                    ->live()
                                    ->inline(),
                                
                                TextInput::make('drug_allergy_notes')
                                    ->label('What Allergy?')
                                    ->hidden(fn (Get $get) => ! $get('drug_allergy'))
                                    ->required(fn (Get $get) => $get('drug_allergy')),
                                
                                Textarea::make('chief_complaint')
                                    ->maxLength(65535),
                                Textarea::make('action_taken')
                                    ->maxLength(65535),
                                Textarea::make('impression')
                                    ->maxLength(65535),
                                Textarea::make('hpi_notes')
                                    ->maxLength(65535),

                                Textarea::make('notes')
                                    ->label('Additional Notes')
                                    ->rows(3)
                                    ->maxLength(1000),
                            ])
                    ])
                ])
        ];
    }

    public static function referralForm()
    {
        return [
            Fieldset::make()
                ->schema([
                    ToggleButtons::make('urgency')
                        ->required()
                        ->options([
                            'Emergency' => 'Emergency',
                            'Ambulatory' => 'Ambulatory',
                            'Medico-Legal' => 'Medico-Legal',
                        ])
                        ->inline(),
                    Select::make('referred_to')
                        ->options([
                            'Carmen MHO' => 'Carmen MHO',
                        ])
                        ->default('Carmen MHO')
                        ->required(),
                    Select::make('status')
                        ->searchable()
                        ->live()
                        ->options([
                            'pending' => 'Pending',
                            'accepted' => 'Accepted',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('pending')
                        ->required(),
                    ToggleButtons::make('surgical_operation')
                        ->boolean()
                        ->live()
                        ->inline(),
                    TextInput::make('procedure')
                        ->label('What Procedure?')
                        ->columnSpan(2)
                        ->disabled(fn (Get $get) => ! $get('surgical_operation'))
                        ->required(fn (Get $get) => $get('surgical_operation')),
                    ToggleButtons::make('drug_allergy')
                        ->boolean()
                        ->live()
                        ->inline(),
                    TextInput::make('drug_allergy_notes')
                        ->label('What?')
                        ->columnSpan(2)
                        ->disabled(fn (Get $get) => ! $get('drug_allergy'))
                        ->required(fn (Get $get) => $get('drug_allergy')),
                ])
                ->columns(3),
            
            Fieldset::make()
                ->schema([
                    Radio::make('referral_reason')
                        ->options([
                            'Hospital Capability' => 'Hospital Capability',
                            'Lack of Specialists' => 'Lack of Specialists',
                            'Financial Constraint' => 'Financial Constraint',
                            'Other' => 'Other',
                        ])
                        ->columns(2)
                        ->live()
                        ->required()
                        ->gridDirection('row'),
                    
                    Textarea::make('reason_for_referral_other')
                        ->label('State your reason for referral')
                        ->disabled(fn (Get $get) => $get('referral_reason') !== 'Other')
                        ->required(fn (Get $get) => $get('referral_reason') === 'Other')
                        ->maxLength(65535)
                ])
                ->columns(2),
                
            Textarea::make('chief_complaint')
                ->maxLength(65535),
            Textarea::make('action_taken')
                ->maxLength(65535),
            Textarea::make('impression')
                ->maxLength(65535),
            Textarea::make('hpi_notes')
                ->label('HPI Notes')
                ->maxLength(65535),
            Placeholder::make('Note')
                ->label('Note:')
                ->columnSpanFull()
                ->content('Referring Facility to retain a duplicate copy of Clinical Referral Form for Record Purposes and Data Profiling; Please attach laboratory work-ups'),
        ];
    }
}