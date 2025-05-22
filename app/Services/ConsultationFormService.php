<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Purok;
use App\Enums\SexEnum;
use App\Models\Patient;
use Filament\Forms\Get;
use App\Models\Barangay;
use App\Models\Category;
use App\Enums\CivilStatusEnum;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use App\Enums\EducationalAttainmentEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\CheckboxList;
use App\Services\PatientFormOptionsServices;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use App\Services\ConsultationFormOptionServices;

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
            Section::make('Patient Information')
                    ->schema([
                        Select::make('patient_id')
                            ->label('Patient')
                            ->reactive()
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
                        DateTimePicker::make('date')
                            ->default(Carbon::now())
                            ->disabledOn('edit')
                            ->required(),
                    ])->columns(2),
                
                Section::make('Patient Information')
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

                                        $enum = CivilStatusEnum::tryFrom($patient->civil_status);
                                        return $enum ? $enum->getLabel() : '-';
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
                                    ->label('Educational Attainment')
                                    ->content(function (Get $get) {
                                        $patientId = $get('patient_id');
                                        if (!$patientId) return '-';

                                        $patient = Patient::find($patientId);
                                        if (!$patient) return '-';

                                        $enum = EducationalAttainmentEnum::tryFrom($patient->educational_attainment);
                                        return $enum ? $enum->getLabel() : '-';
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
                                            ->options([
                                                'male' => 'Male',
                                                'female' => 'Female',
                                            ])
                                            ->required(),
                                        Select::make('civil_status')
                                            ->label('Civil Status')
                                            ->options(PatientFormOptionsServices::getPatientStatus())
                                            ->required(),
                                        DatePicker::make('birth_date')->label('Date of Birth')->required(),
                                        Select::make('educational_attainment')
                                            ->label('Educational Attainment')
                                            ->columnSpanFull()
                                            ->options(PatientFormOptionsServices::getPatientEducationalAttainment()),
                                    ])
                                    ->columns(2),
                                Fieldset::make('Medical Information')
                                    ->schema([
                                        Select::make('category_id')
                                            ->label('Category')
                                            ->columnSpanFull()
                                            ->options(Category::query()->get()->pluck('name', 'id')->toArray())
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

                Section::make('Consultation Details')
                    ->schema([
                        Select::make('purok_id')
                            ->label('Purok')
                            ->required()
                            ->columnSpanFull()
                            ->options(Purok::query()->get()->pluck('name', 'id')->toArray())
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required(),
                                Select::make('barangay_id')
                                    ->label('Barangay')
                                    ->required()  
                                    ->options(Barangay::query()->get()->pluck('name', 'id')->toArray()),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                return Purok::create($data)->getKey();
                            }),
                        Fieldset::make()
                            ->schema([
                                ToggleButtons::make('disability')
                                    ->label('With Disability?')
                                    ->boolean()
                                    ->reactive()
                                    ->inline(),
                                ToggleButtons::make('philhealth')
                                    ->label('With Philhealth?')
                                    ->boolean()
                                    ->inline(),
                                ToggleButtons::make('member_of_4ps')
                                    ->label('4ps member?')
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
                                ToggleButtons::make('type')
                                    ->inline()
                                    ->inlineLabel(false)
                                    ->options(fn () => ConsultationFormOptionServices::getTypeOptions()),
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
                            ])
                            ->visible(function (Get $get) {
                                $patientId = $get('patient_id');
                                if (!$patientId) return false;
                                
                                $patient = Patient::with('category')->find($patientId);
                                if (!$patient->category) return false;

                                return $patient->category->is_child;
                            })
                            ->columns(3),
                        Fieldset::make('Child Information')
                            ->schema([
                                TextInput::make('child_weight')
                                    ->label('Child Weight')
                                    ->numeric() 
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

                Section::make('Referral')
                    ->description('If this patient needs to be referred, mark status as "Needs Referral" or "Referred" and save first, then create a referral.')
                    ->schema([
                        Placeholder::make('referral_instructions')
                            ->content('After saving this consultation, you can create a referral from the consultation details page.'),
                    ])
                    ->visible(fn (Get $get) => in_array($get('status'), ['needs_referral', 'referred'])),

                Hidden::make('user_id')
                    ->default(fn () => auth()->id())
                    ->required(),
        ];
    }
}