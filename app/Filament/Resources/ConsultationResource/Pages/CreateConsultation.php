<?php
namespace App\Filament\Resources\ConsultationResource\Pages;

use App\Models\Patient;
use App\Models\Referral;
use Illuminate\Support\Arr;
use App\Models\Consultation;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use App\Services\PDFGenerationService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ConsultationResource;

class CreateConsultation extends CreateRecord
{
    protected static string $resource = ConsultationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Consultation
    {
        return DB::transaction(function () use ($data) {
            logger($data);

            try {
                // Create consultation with filtered data
                $consultationData            = $this->prepareConsultationData($data);
                $consultationData['user_id'] = Auth::id();
                $consultation                = Consultation::create($consultationData);

                // Create referral if needed
                if ($this->shouldCreateReferral($data)) {
                    $this->createReferral($data, $consultation);
                }

                return $consultation;

            } catch (\Exception $e) {
                logger("Error creating consultation: " . $e->getMessage(), [
                    'data'  => $data,
                    'error' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    private function prepareConsultationData(array $data): array
    {
        return Arr::except($data, [
            'referred_to',
            'referral_reason',
            'urgency',
            'surgical_operation',
            'reason_for_referral_other',
            'procedure',
            'drug_allergy',
            'drug_allergy_notes',
            'chief_complaint',
            'action_taken',
            'impression',
            'hpi_notes',
            'notes',
        ]);
    }

    /**
     * Check if referral should be created based on required fields
     */
    private function shouldCreateReferral(array $data): bool
    {
        return ! empty($data['referred_to']) &&
        ! empty($data['referral_reason']) &&
        ! empty($data['urgency']);
    }

    /**
     * Create referral with auto-generated reference ID
     */
    private function createReferral(array $data, Consultation $consultation): void
    {
        $currentDate = now();
        $refId       = $this->generateReferralId($currentDate, $consultation->patient->barangay_id);

        $referralData = $this->prepareReferralData($data, $consultation, $refId, $currentDate);

        Referral::create($referralData);
    }

    /**
     * Generate unique referral ID with race condition protection
     */
    private function generateReferralId($currentDate, $brgyId): string
    {
        $dateFormat = $currentDate->format('Ymd');
        $prefix     = 'REF';

        // Use database transaction with lock to prevent race conditions
        $sequenceNumber = DB::transaction(function () use ($currentDate) {
            $todaysCount = \App\Models\Referral::whereDate('created_at', $currentDate->toDateString())
                ->lockForUpdate()
                ->count();

            return $todaysCount + 1;
        });

        $autoIncrement = str_pad($sequenceNumber, 3, '0', STR_PAD_LEFT);

        return "{$prefix}-{$dateFormat}-{$autoIncrement}-{$brgyId}";
    }

    /**
     * Prepare referral data array
     */
    private function prepareReferralData(array $data, Consultation $consultation, string $refId, $currentDate): array
    {
        return [
            'ref_id'                    => $refId,
            'consultation_id'           => $consultation->id,
            'patient_id'                => $data['patient_id'],
            'referred_to'               => $data['referred_to'],
            'referral_reason'           => $data['referral_reason'],
            'reason_for_referral_other' => $data['reason_for_referral_other'] ?? null,
            'urgency'                   => $data['urgency'],
            'surgical_operation'        => $data['surgical_operation'] ?? false,
            'procedure'                 => $data['procedure'] ?? null,
            'drug_allergy'              => $data['drug_allergy'] ?? false,
            'drug_allergy_notes'        => $data['drug_allergy_notes'] ?? null,
            'chief_complaint'           => $data['chief_complaint'] ?? null,
            'action_taken'              => $data['action_taken'] ?? null,
            'impression'                => $data['impression'] ?? null,
            'hpi_notes'                 => $data['hpi_notes'] ?? null,
            'receiving_provider_notes'  => $data['notes'] ?? null,
            'user_id'                   => Auth::id(),
            'created_at'                => $currentDate,
        ];
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Consultation Created!')
            ->success()
            ->body('Consultation has been created and saved.');
    }

    // Add this helper method to get step count
    protected function getSteps(): array
    {
        $wizard = $this->form->getComponent('data');
        if ($wizard instanceof \Filament\Forms\Components\Wizard) {
            return $wizard->getChildComponentContainer()->getComponents();
        }
        return [];
    }

    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
            ->label('Submit')
            ->disabled(function () {
                try {
                    // Validate the form
                    $this->form->validate();
                    return false; // Enable button if validation passes
                } catch (\Illuminate\Validation\ValidationException $e) {
                    return true; // Disable button if validation fails
                } catch (\Exception $e) {
                    return true; // Disable button on any error
                }
            })
            ->before(function () {
                try {
                    $this->form->validate();

                    $formData = $this->form->getState();
                    if (empty(array_filter($formData))) {
                        throw new \Exception('Please fill in all required fields before proceeding.');
                    }

                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Form Incomplete')
                        ->body($e->getMessage())
                        ->warning()
                        ->send();

                    $this->halt();
                }
            })
            ->requiresConfirmation()
            ->modalHeading(function () {
                try {
                    $this->form->validate();

                    $formData = $this->form->getState();
                    if (empty(array_filter($formData))) {
                        throw new \Exception('Please fill in all required fields before proceeding.');
                    }
                } catch (\Illuminate\Validation\ValidationException $e) {
                    return 'Fill in all required fields before proceeding.';
                } catch (\Exception $e) {
                    return 'Fill in all required fields before proceeding.';
                }
            })
            ->modalWidth(MaxWidth::FiveExtraLarge)
            ->modalDescription(function () {
                try {
                    $this->form->validate();

                    $formData = $this->form->getState();

                    if (empty(array_filter($formData))) {
                        throw new \Exception('Please fill in all required fields before proceeding.');
                    }

                    if (!empty($formData['patient_id'])) {
                        $patientData = \App\Models\Patient::find($formData['patient_id']);
                        $formData['first_name'] = $patientData->first_name;
                        $formData['middle_name'] = $patientData->middle_name;
                        $formData['last_name'] = $patientData->last_name;
                        $formData['suffix'] = $patientData->suffix;
                        $formData['birth_date'] = $patientData->birth_date;
                        $formData['age'] = $patientData->age;
                        $formData['sex'] = $patientData->sex;
                        $formData['civil_status'] = $patientData->civil_status;
                        $formData['educational_attainment'] = $patientData->educational_attainment;
                        $formData['occupation'] = $patientData->occupation;
                        $formData['family_monthly_income'] = $patientData->family_monthly_income;
                        $formData['no_of_house'] = $patientData->no_of_house;
                        $formData['house_type'] = $patientData->house_type;
                    }

                    // Remove dd() for production - uncomment for debugging
                    // dd($formData);

                    return new HtmlString(
                        view('filament.create-preview', compact('formData'))->render()
                    );
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $html = '<div  class="flex flex-col w-full h-full overflow-auto gap-3">';

                    foreach ($e->errors() as $fieldErrors) {
                        $html .= "<div class=\"flex items-center gap-2 p-3 rounded bg-red-100";
                        foreach ($fieldErrors as $message) {
                            $html .= "
                                            <x-heroicon-o-exclamation-circle class=\"w-5 h-5\" />
                                            <span>{$message}</span>
                                        ";
                        }
                        $html .= '</div>';
                    }

                    $html .= '</div>';

                    return new HtmlString($html);
                } catch (\Exception $e) {
                    // Fallback for general exceptions
                    $html = '<div class="flex items-center gap-2 p-3 rounded bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400">
                                <x-heroicon-o-exclamation-circle class="w-5 h-5" />
                                <span>{$e->getMessage()}</span>
                            </div>';

                    return new HtmlString($html);
                }
            })
            ->action(function () {
                $this->create();
            })
            ->after(function () {
                $record = $this->getRecord();

                if ($record && $record->referral()->exists()) {
                    $referral = $record->referral;

                    // Open the View modal for this record
                    $this->redirect(
                        static::getResource()::getUrl('view', ['record' => $record->id])
                    );
                }
            })
            ->keyBindings(['mod+s']);
    }

    /**
     * Get display name for relationship fields
     */
    protected function getRelationshipDisplayName(string $key, $value): ?string
    {
        try {
            // Map common relationship fields to their models
            $relationshipMap = [
                'patient_id'     => \App\Models\Patient::class,
                'user_id'        => \App\Models\User::class,
                'barangay_id'    => \App\Models\Barangay::class,
                // Add more mappings as needed
            ];

            if (! isset($relationshipMap[$key])) {
                return null;
            }

            $modelClass = $relationshipMap[$key];
            $model      = $modelClass::find($value);

            if (! $model) {
                return null;
            }

            // Try common name fields
            $nameFields = ['name', 'full_name', 'title', 'label', 'description'];

            foreach ($nameFields as $field) {
                if (isset($model->$field)) {
                    return $model->$field;
                }
            }

            // For User model, try combining first_name and last_name
            if ($model instanceof \App\Models\User) {
                if (isset($model->first_name) && isset($model->last_name)) {
                    return trim($model->first_name . ' ' . $model->last_name);
                }
                if (isset($model->email)) {
                    return $model->email;
                }
            }

            // For Patient model, similar logic
            if ($model instanceof \App\Models\Patient) {
                if (isset($model->first_name) && isset($model->last_name)) {
                    return trim($model->first_name . ' ' . $model->last_name);
                }
            }

            return null;
        } catch (\Exception $e) {
            logger('Error getting relationship display name: ' . $e->getMessage());
            return null;
        }
    }

    protected function getPersonTypeName($value)
    {
        try {
            // Get the form schema to find the relationship
            $schema = $this->form->getSchema();

            foreach ($schema as $component) {
                if ($component->getName() === 'type') {
                    $relationship = $component->getRelationship();
                    $relatedModel = $component->getRelationship()->getRelated();
                    $record       = $relatedModel::find($value);

                    if ($record) {
                        return $record->name ??
                            "ID: {$value}";
                    }
                }
            }

            return "ID: {$value}";
        } catch (\Exception $e) {
            return "ID: {$value}";
        }
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Create Another')
            ->disabled(function () {
                try {
                    $this->form->validate();
                    return false;
                } catch (\Illuminate\Validation\ValidationException $e) {
                    return true;
                } catch (\Exception $e) {
                    return true;
                }
            });
    }
}