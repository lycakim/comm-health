<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Purok;
use App\Enums\SexEnum;
use App\Models\Patient;
use Filament\Forms\Get;
use App\Models\Referral;
use Filament\Forms\Form;
use App\Enums\UrgencyEnum;
use Filament\Tables\Table;
use App\Models\Consultation;
use App\Enums\CivilStatusEnum;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use App\Enums\EducationalAttainmentEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Actions\Action;
use App\Filament\Resources\ReferralResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ReferralResource\RelationManagers;

class ReferralResource extends Resource
{
    protected static ?string $model = Referral::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 3;

    // protected static ?string $recordTitleAttribute = 'id';

    public static function canAccess(): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isMHO() || auth()->user()->isBHW();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Referral Information')
                    ->schema([
                        Select::make('patient_id')
                            ->label('Patient')
                            ->options(
                                Patient::query()
                                    ->get()
                                    ->mapWithKeys(function ($patient) {
                                        return [$patient->id => "{$patient->first_name} {$patient->last_name}"];
                                    })
                                    ->sort()
                                    ->toArray()
                            )
                            ->searchable()
                            ->reactive()
                            ->disabled(fn ($get) => !empty($get('consultation_id'))),

                        Select::make('consultation_id')
                            ->label('Consultation')
                            ->options(
                                Consultation::query()
                                    ->with('patient') // eager load patient relation
                                    ->get()
                                    ->mapWithKeys(function ($consultation) {
                                        $patientName = $consultation->patient ? "{$consultation->patient->first_name} {$consultation->patient->last_name}" : 'No Patient';
                                        $consultationDate = $consultation->created_at ? $consultation->created_at->format('M d, Y') : 'No Date';
                                        return [$consultation->id => "{$patientName} - Consultation #{$consultation->id} ({$consultationDate})"];
                                    })
                                    ->sort()
                                    ->toArray()
                            )
                            ->searchable()
                            ->reactive()
                            ->disabled(fn ($get) => !empty($get('patient_id'))),
                    ])->columns(2),
                
                Section::make('Patient Information')
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
                                        if (!$patient) return '-';

                                        return $patient->category ? $patient->category->name : '-';
                                    }),
                            ])
                            ->columns(3)
                    ])->columns(3)
                    ->visible(fn (Forms\Get $get) => (bool) $get('patient_id')),

                Section::make('Patient Information')
                    ->schema([
                        Forms\Components\Fieldset::make('Patient')
                            ->schema([
                                Forms\Components\Placeholder::make('patient_name')
                                    ->label('Patient Name')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        return $consultation ? $consultation->patient->first_name . ' ' . $consultation->patient->middle_name . ' ' . $consultation->patient->last_name : '-';
                                    }),
                                Forms\Components\Placeholder::make('gender')
                                    ->label('Gender')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::with('patient')->find($consultationId);
                                        if (!$consultation) return '-';

                                        // return $consultation;
                                        $enum = SexEnum::tryFrom($consultation->patient->sex);
                                        return $enum ? $enum->getLabel() : '-';
                                    }),
                                Forms\Components\Placeholder::make('civil_status')
                                    ->label('Civil Status')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::with('patient')->find($consultationId);
                                        if (!$consultation) return '-';

                                        return $consultation->patient->civil_status ?? '-';
                                    }),
                                Forms\Components\Placeholder::make('age')
                                    ->label('Age')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        return $consultation ? $consultation->patient->age : '-';
                                    }),
                                Forms\Components\Placeholder::make('educational_attainment')
                                    ->label('Educational Attainment')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::with('patient')->find($consultationId);
                                        if (!$consultation) return '-';

                                        return $consultation->patient->educational_attainment;
                                    }),
                                Forms\Components\Placeholder::make('birth_date')
                                    ->label('Date of Birth')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        return $consultation ? Carbon::parse($consultation->patient->birth_date)->format('M d, Y') : '-';
                                    }),
                                Forms\Components\Placeholder::make('address')
                                    ->label('Address')
                                    ->columnSpanFull()
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        if (!$consultation->purok_id) return '-';
                                        
                                        $purok = Purok::find($consultation->purok_id);
                                        return $purok ? $purok->name . ', ' . $purok->barangay->name : '-';
                                    }),
                            ])
                            ->columns(3),
                        Fieldset::make('Medical History')
                            ->schema([
                                Forms\Components\Placeholder::make('blood_pressure')
                                    ->label('Blood Pressure (mm Hg)')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        return $consultation ? $consultation->patient->blood_pressure : '-';
                                    }),
                                Forms\Components\Placeholder::make('sugar_level')
                                    ->label('Sugar Level (mm/dl)')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        return $consultation ? $consultation->patient->sugar_level : '-';
                                    }),
                                Forms\Components\Placeholder::make('height')
                                    ->label('Height (cm)')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        return $consultation ? $consultation->patient->height : '-';
                                    }),
                                Forms\Components\Placeholder::make('weight')
                                    ->label('Weight (kg)')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        return $consultation ? $consultation->patient->weight : '-';
                                    }),
                                Forms\Components\Placeholder::make('bmi')
                                    ->label('BMI')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        return $consultation ? $consultation->patient->bmi : '-';
                                    }),
                                Forms\Components\Placeholder::make('bmi_category')
                                    ->label('BMI Category')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        return $consultation ? $consultation->patient->bmi_category : '-';
                                    }),
                                Forms\Components\Placeholder::make('health_statuses')
                                    ->label('Health Statuses')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';

                                        $consultation = Consultation::find($consultationId);
                                        if (!$consultation) return '-';

                                        $statuses = $consultation->patient->health_statuses;

                                        $statusesArray = is_string($statuses) ? explode(',', $statuses) : (array) $statuses;

                                        $statusesArray = array_filter(array_map('trim', $statusesArray));

                                        return implode(', ', $statusesArray);
                                    }),
                                Forms\Components\Placeholder::make('medication_maintenance')
                                    ->label('Medication/Maintenance')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';

                                        $consultation = Consultation::find($consultationId);
                                        if (!$consultation) return '-';

                                        $meds = $consultation->patient->medication_maintenance;
                                        $medsArray = is_string($meds) ? explode(',', $meds) : (array) $meds;
                                        $medsArray = array_filter(array_map('trim', $medsArray));

                                        return implode(', ', $medsArray);
                                    }),
                                Forms\Components\Placeholder::make('category')
                                    ->label('Category')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        return $consultation ? $consultation->patient->category->name : '-';
                                    }),
                            ])
                            ->columns(3),
                        Forms\Components\Fieldset::make('Consultation Details')
                            ->schema([
                                Forms\Components\Placeholder::make('With Disability?')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        if (!$consultation) return '-';

                                        if ($consultation->disability) {
                                            if ($consultation->disability && is_array($consultation->disabilities)) {
                                                return 'Yes: ' . implode(', ', $consultation->disabilities);
                                            }
                                            
                                            // If no specific disabilities are stored but with_disability is true
                                            return 'Yes';
                                        } else {
                                            return 'No';
                                        }
                                    }),
                                Forms\Components\Placeholder::make('With Philhealth?')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        if (!$consultation) return '-';

                                        return $consultation->philhealth ? 'Yes' : 'No';
                                    }),
                                Forms\Components\Placeholder::make('4ps Member?')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        if (!$consultation) return '-';

                                        return $consultation->member_of_4ps ? 'Yes' : 'No';
                                    }),
                                Forms\Components\Placeholder::make('NHTS Member?')
                                    ->label('NHTS Member?')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        if (!$consultation) return '-';

                                        return $consultation->nhts_member ? 'Yes' : 'No';
                                    }),
                                Forms\Components\Placeholder::make('Birth Plan?')
                                    ->label('Birth Plan?')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        if (!$consultation) return '-';

                                        return $consultation->birth_plan ? 'Yes' : 'No';
                                    }),
                                Forms\Components\Placeholder::make('Type')
                                    ->label('Patient Type')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        if (!$consultation) return '-';

                                        return $consultation->type;
                                    }),
                                Forms\Components\Placeholder::make('mother_name')
                                    ->label('Mother\'s Name')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        return $consultation && $consultation->first_name && $consultation->mother->last_name ? $consultation->mother->first_name . ' ' . $consultation->mother->middle_name . ' ' . $consultation->mother->last_name : '-';
                                    }),
                                Forms\Components\Placeholder::make('weight')
                                    ->label('Child Weight')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        return $consultation && $consultation->child_weight ? $consultation->child_weight : '-';
                                    }),
                                Forms\Components\Placeholder::make('child_order')
                                    ->label('Child Order')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        if (!$consultation) return '-';
                                        
                                        $number = $consultation->child_order;
                                        $suffix = match ($number % 10) {
                                            1 => $number % 100 === 11 ? 'th' : 'st',
                                            2 => $number % 100 === 12 ? 'th' : 'nd',
                                            3 => $number % 100 === 13 ? 'th' : 'rd',
                                            default => 'th',
                                        };
                                        
                                        return $number ? $number . $suffix : '-';
                                    }),
                                Forms\Components\Placeholder::make('mother_status')
                                    ->label('Mother\'s Status')
                                    ->helperText('TT/TD Status of mother/CPAB')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        return $consultation ? ($consultation->mother_status ? 'Yes' : 'No') : '-';
                                    }),
                                Forms\Components\Placeholder::make('hepa_b')
                                    ->label('Hepa B')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        return $consultation ? ($consultation->hepa_b ? 'Yes' : 'No') : '-';
                                    }),
                                Forms\Components\Placeholder::make('nbs')
                                    ->label('NBS')
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        return $consultation ? ($consultation->nbs ? 'Yes' : 'No') : '-';
                                    }),
                                Forms\Components\Placeholder::make('notes')
                                    ->label('Notes')
                                    ->columnSpanFull()
                                    ->content(function (Forms\Get $get) {
                                        $consultationId = $get('consultation_id');
                                        if (!$consultationId) return '-';
                                        
                                        $consultation = Consultation::find($consultationId);
                                        return $consultation ? $consultation->notes : '-';
                                    }),
                            ])
                            ->columns(3),
                    ])->columns(3)
                    ->visible(fn (Forms\Get $get) => (bool) $get('consultation_id')),

                Section::make('Referral Details')
                    ->schema([
                        Forms\Components\Fieldset::make()
                            ->schema([
                                Forms\Components\ToggleButtons::make('urgency')
                                    ->required()
                                    ->options([
                                        'Emergency' => 'Emergency',
                                        'Ambulatory' => 'Ambulatory',
                                        'Medico-Legal' => 'Medico-Legal',
                                    ])
                                    ->inline(),
                                Forms\Components\Select::make('referred_to')
                                    ->options([
                                        'Carmen MHO' => 'Carmen MHO',
                                    ])
                                    ->default('Carmen MHO')
                                    ->required(),
                                Forms\Components\Select::make('status')
                                    ->searchable()
                                    ->options([
                                        'pending' => 'Pending',
                                        'accepted' => 'Accepted',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('pending')
                                    ->required(),
                                Forms\Components\ToggleButtons::make('surgical_operation')
                                    ->boolean()
                                    ->live()
                                    ->inline(),
                                Forms\Components\TextInput::make('procedure')
                                    ->label('What Procedure?')
                                    ->columnSpan(2)
                                    ->disabled(fn (Forms\Get $get) => ! $get('surgical_operation'))
                                    ->required(fn (Forms\Get $get) => $get('surgical_operation')),
                                Forms\Components\ToggleButtons::make('drug_allergy')
                                    ->boolean()
                                    ->live()
                                    ->inline(),
                                Forms\Components\TextInput::make('drug_allergy_notes')
                                    ->label('What?')
                                    ->columnSpan(2)
                                    ->disabled(fn (Forms\Get $get) => ! $get('drug_allergy'))
                                    ->required(fn (Forms\Get $get) => $get('drug_allergy')),
                            ])
                            ->columns(3),
                        // Forms\Components\Fieldset::make('Physical Examination')
                        //     ->schema([
                        //         Forms\Components\TextInput::make('height')
                        //             ->numeric()
                        //             ->hint('cm')
                        //             ->label('Height'),
                        //         Forms\Components\TextInput::make('weight')
                        //             ->numeric()
                        //             ->hint('kg')
                        //             ->label('Weight'),
                        //         Forms\Components\TextInput::make('blood_pressure')
                        //             ->numeric()
                        //             ->hint('mm Hg')
                        //             ->label('Blood Pressure'),
                        //         Forms\Components\TextInput::make('rr')
                        //             ->numeric()
                        //             ->hint('bpm')
                        //             ->label('Respiratory Rate'),
                        //     ])
                        //     ->columns(4),
                        Forms\Components\Fieldset::make()
                            ->schema([
                                Forms\Components\Radio::make('referral_reason')
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
                                Forms\Components\Textarea::make('reason_for_referral_other')
                                    ->label('State your reason for referral')
                                    ->disabled(fn (Forms\Get $get) => $get('referral_reason') !== 'Other')
                                    ->required(fn (Forms\Get $get) => $get('referral_reason') === 'Other')
                                    ->maxLength(65535)
                            ])
                            ->columns(2),
                        Forms\Components\Textarea::make('chief_complaint')
                            ->maxLength(65535),
                        Forms\Components\Textarea::make('action_taken')
                            ->maxLength(65535),
                        Forms\Components\Textarea::make('impression')
                            ->maxLength(65535),
                        Forms\Components\Textarea::make('hpi_notes')
                            ->label('HPI Notes')
                            ->maxLength(65535),
                        Forms\Components\Placeholder::make('Note')
                            ->label('Note:')
                            ->columnSpanFull()
                            ->content('Referring Facility to retain a duplicate copy of Clinical Referral Form for Record Purposes and Data Profiling; Please attach laboratory work-ups'),
                    ])->columns(2),

                Section::make('Recipient Information')
                    ->schema([
                        Forms\Components\TextInput::make('receiving_provider_name')
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('date_completed'),
                        Forms\Components\Textarea::make('receiving_provider_notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn (Forms\Get $get) => in_array($get('status'), ['accepted', 'completed'])),

                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id())
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Reference ID')
                    ->formatStateUsing(function ($record) {
                        if ($record->consultation) {
                            return 'Consultation #' . $record->consultation_id . ' - ' . $record->consultation->patient->first_name . ' ' . $record->consultation->patient->last_name;
                        }
                        elseif ($record->patient_id && $record->patient) {
                            return 'Patient #' . $record->patient_id . ' - ' . $record->patient->first_name . ' ' . $record->patient->last_name;
                        }
                        else {
                            return 'No ID';
                        }
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->with(['consultation', 'patient'])
                            ->where('id', 'like', "%{$search}%")
                            ->orWhereHas('patient', function (Builder $patientQuery) use ($search) {
                                $patientQuery->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('consultation', function (Builder $consultationQuery) use ($search) {
                                $consultationQuery->whereHas('patient', function (Builder $patientQuery) use ($search) {
                                    $patientQuery->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%");
                                });
                            });
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Referred On')
                    ->since()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('urgency')
                    ->badge()
                    ->color(function (string $state): string {
                        $enum = UrgencyEnum::tryFrom($state);
                        return $enum?->getColor() ?? 'gray';
                    })
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => 
                        UrgencyEnum::tryFrom($state)?->getLabel() ?? $state),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->searchable()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'accepted' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Referred By')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->searchable()
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('urgency')
                    ->options([
                        'routine' => 'Routine',
                        'urgent' => 'Urgent',
                        'emergency' => 'Emergency',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('barangay_id')
                    ->relationship('barangay', 'name')
                    ->label('Barangay'),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date_referred', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date_referred', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\Action::make('download_pdf')
                //     ->label('Download PDF')
                //     ->url(fn (Referral $record) => route('referrals.pdf', $record))
                //     ->icon('heroicon-o-document-download')
                //     ->openUrlInNewTab(),
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
            'index' => Pages\ListReferrals::route('/'),
            'create' => Pages\CreateReferral::route('/create'),
            'edit' => Pages\EditReferral::route('/{record}/edit'),
            'view' => Pages\ViewReferral::route('/{record}/view'),
        ];
    }
}