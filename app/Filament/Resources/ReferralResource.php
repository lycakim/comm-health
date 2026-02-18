<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Purok;
use App\Enums\SexEnum;
use App\Enums\RoleEnum;
use App\Models\Patient;
use Filament\Forms\Get;
use App\Models\Referral;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Consultation;
use App\Enums\CivilStatusEnum;
use App\Traits\HasUserTypeUrls;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Fieldset;
use App\Exports\ReferralsExport;
use App\Enums\EducationalAttainmentEnum;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms\Components\Actions\Action;
use App\Filament\Resources\ReferralResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ReferralResource\RelationManagers;

class ReferralResource extends Resource
{
    // use HasUserTypeUrls;
    
    protected static ?string $model = Referral::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 3;

    // protected static ?string $recordTitleAttribute = 'id';

    public static function canAccess(): bool
    {
        return in_array(self::currentUser()->role, [
            RoleEnum::ADMIN,
            RoleEnum::MHO,
        ]);
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
                                Forms\Components\DateTimePicker::make('date_referred')
                                    ->label('Date & Time Referred')
                                    ->default(now())
                                    ->required()
                                    ->displayFormat('M d, Y h:i A')
                                    ->timezone('Asia/Manila'),
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
                    ->default(fn () => self::currentUser()->id)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('format')
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
                                    ->relationship('patient.purok', 'name')
                                    ->searchable()
                                    ->preload(),
                            ])
                            ->columns(2),
                    ])
                    ->action(function (array $data) {
                        $query = Referral::with(['patient', 'patient.purok', 'patient.barangay', 'consultation.patient', 'consultation.patient.purok', 'consultation.patient.barangay', 'user']);
                        
                        // Apply filters
                        $hasFilters = !empty($data['age_min']) || !empty($data['age_max']) || !empty($data['gender']) || !empty($data['purok_id']);
                        
                        if ($hasFilters) {
                            $query->where(function (Builder $q) use ($data) {
                                $q->whereHas('patient', function (Builder $patientQuery) use ($data) {
                                    if (!empty($data['age_min'])) {
                                        $patientQuery->where('age', '>=', $data['age_min']);
                                    }
                                    if (!empty($data['age_max'])) {
                                        $patientQuery->where('age', '<=', $data['age_max']);
                                    }
                                    if (!empty($data['gender'])) {
                                        $patientQuery->where('sex', $data['gender']);
                                    }
                                    if (!empty($data['purok_id'])) {
                                        $patientQuery->where('purok_id', $data['purok_id']);
                                    }
                                })
                                ->orWhereHas('consultation.patient', function (Builder $patientQuery) use ($data) {
                                    if (!empty($data['age_min'])) {
                                        $patientQuery->where('age', '>=', $data['age_min']);
                                    }
                                    if (!empty($data['age_max'])) {
                                        $patientQuery->where('age', '<=', $data['age_max']);
                                    }
                                    if (!empty($data['gender'])) {
                                        $patientQuery->where('sex', $data['gender']);
                                    }
                                    if (!empty($data['purok_id'])) {
                                        $patientQuery->where('purok_id', $data['purok_id']);
                                    }
                                });
                            });
                        }

                        $referrals = $query->get();

                        if ($referrals->isEmpty()) {
                            \Filament\Notifications\Notification::make()
                                ->title('No Data Found')
                                ->body('No referrals match the selected filters.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $user = Auth::user();
                        $barangay = $user->barangay_id ? \App\Models\Barangay::find($user->barangay_id) : null;
                        $barangayName = $barangay ? $barangay->name : 'All Barangays';
                        $province = config('app.province', 'DAVAO DEL NORTE');
                        $municipality = config('app.municipality', 'CARMEN');
                        $dateTime = now()->format('F d, Y h:i A');
                        $reportTitle = 'Referrals Report';

                        if ($data['format'] === 'xlsx') {
                            $user = Auth::user();
                            $user->load('barangay');
                            return Excel::download(
                                new ReferralsExport($referrals, $reportTitle, $barangay, $user->getPreparedByLabelForExport()),
                                'referrals_export_' . now()->format('Y-m-d_His') . '.xlsx',
                                \Maatwebsite\Excel\Excel::XLSX
                            );
                        }

                        if ($data['format'] === 'csv') {
                            return response()->streamDownload(function () use ($referrals, $province, $municipality, $barangayName, $reportTitle, $dateTime) {
                                $handle = fopen('php://output', 'w');
                                
                                // Add UTF-8 BOM for Excel compatibility
                                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
                                
                                // Add header rows (matching xlsx format)
                                fputcsv($handle, ['REPUBLIC OF THE PHILIPPINES', '', '', '', '', '', '', '', '', '', '']);
                                fputcsv($handle, ['PROVINCE OF ' . strtoupper($province), '', '', '', '', '', '', '', '', '', '']);
                                fputcsv($handle, ['MUNICIPAL HEALTH OFFICE', '', '', '', '', '', '', '', '', '', '']);
                                fputcsv($handle, ['MUNICIPALITY OF ' . strtoupper($municipality), '', '', '', '', '', '', '', '', '', '']);
                                fputcsv($handle, ['BARANGAY ' . strtoupper($barangayName), '', '', '', '', '', '', '', '', '', '']);
                                fputcsv($handle, [strtoupper($reportTitle), '', '', '', '', '', '', '', '', '', '']);
                                fputcsv($handle, ['', '', '', '', '', '', '', '', '', '', '']); // Empty row
                                fputcsv($handle, ['As of : ' . $dateTime, '', '', '', '', '', '', '', '', '', '']);
                                
                                // Column headers
                                fputcsv($handle, [
                                    'Reference ID',
                                    'Patient Name',
                                    'Age',
                                    'Gender',
                                    'Purok',
                                    'Barangay',
                                    'Urgency',
                                    'Status',
                                    'Referred To',
                                    'Referred By',
                                    'Date Referred',
                                ]);
                                
                                // Rows
                                foreach ($referrals as $referral) {
                                    $patient = $referral->patient ?? $referral->consultation?->patient;
                                    fputcsv($handle, [
                                        $referral->id,
                                        $patient ? ($patient->first_name . ' ' . $patient->last_name) : 'N/A',
                                        $patient?->age ?? 'N/A',
                                        $patient?->sex ?? 'N/A',
                                        $patient?->purok?->name ?? 'N/A',
                                        $patient?->barangay?->name ?? 'N/A',
                                        $referral->urgency,
                                        $referral->status,
                                        $referral->referred_to,
                                        $referral->user?->name ?? 'N/A',
                                        $referral->date_referred ? $referral->date_referred->format('Y-m-d H:i:s') : ($referral->created_at->format('Y-m-d H:i:s')),
                                    ]);
                                }
                                
                                // Footer row
                                fputcsv($handle, ['', '', '', '', '', '', '', '', '', '', '']); // Empty row
                                fputcsv($handle, ['Total Records: ' . count($referrals), '', '', '', '', '', '', '', '', '', '']);
                                
                                fclose($handle);
                            }, 'referrals_export_' . now()->format('Y-m-d_His') . '.csv', [
                                'Content-Type' => 'text/csv',
                            ]);
                        } else {
                            // PDF export
                            $pdfService = app(\App\Services\PDFGenerationService::class);
                            $pdf = $pdfService->generateReferralListPdf($referrals, $reportTitle, $barangay);
                            $filename = 'referrals_export_' . now()->format('Y-m-d_His') . '.pdf';
                            
                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                $filename,
                                ['Content-Type' => 'application/pdf']
                            );
                        }
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Reference ID')
                    ->formatStateUsing(function ($record) {
                        if ($record->consultation) {
                            return 'Consultation #' . $record->consultation_id;
                        }
                        if ($record->patient_id && $record->patient) {
                            return 'Patient #' . $record->patient_id;
                        }
                        return 'No ID';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->with(['consultation', 'patient'])
                            ->where('id', 'like', "%{$search}%")
                            ->orWhere('consultation_id', 'like', "%{$search}%")
                            ->orWhere('patient_id', 'like', "%{$search}%");
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('patient_name')
                    ->label('Name')
                    ->getStateUsing(function ($record) {
                        $patient = $record->patient ?? $record->consultation?->patient;
                        return $patient ? trim($patient->first_name . ' ' . $patient->last_name) : 'N/A';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->with(['consultation', 'patient'])
                            ->whereHas('patient', function (Builder $patientQuery) use ($search) {
                                $patientQuery->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('consultation', function (Builder $consultationQuery) use ($search) {
                                $consultationQuery->whereHas('patient', function (Builder $patientQuery) use ($search) {
                                    $patientQuery->where('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%");
                                });
                            });
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Referred')
                    ->since()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Referred By')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('barangay_id')
                    ->relationship('patient.barangay', 'name')
                    ->label('Barangay')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->columnSpan(1),
                        Forms\Components\DatePicker::make('created_until')->columnSpan(1),
                    ])
                    ->columns(2)
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
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
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
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                $query->latest();
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
            'index' => Pages\ListReferrals::route('/'),
            'create' => Pages\CreateReferral::route('/create'),
            'edit' => Pages\EditReferral::route('/{record}/edit'),
            'view' => Pages\ViewReferral::route('/{record}/view'),
        ];
    }

    // initialize auth user
    public static function currentUser(): ?User
    {
        return Auth::user();
    }
}