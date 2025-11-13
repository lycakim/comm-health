<?php

namespace App\Filament\Resources\ConsultationResource\Pages;

use App\Models\User;
use Filament\Actions;
use App\Enums\RoleEnum;
use Filament\Forms\Get;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ToggleButtons;
use App\Filament\Resources\ConsultationResource;

class EditConsultation extends EditRecord
{
    protected static string $resource = ConsultationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
                
            // First action: Simple confirmation that triggers the form modal
            Actions\Action::make('confirm_create_referral')
                ->label('Create Referral')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Informed Consent')
                ->modalDescription(fn($record) => new HtmlString(
                    view('components.referral-consent', [
                        'patient' => $record->patient,
                    ])->render()
                ))
                ->modalSubmitActionLabel('Proceed')
                ->action(function ($record) {
                    $this->js("
                        setTimeout(() => {
                            const button = document.querySelector('[wire\\\\:click*=\"mountAction(\\'create_referral\\')\"]:not([style*=\"display: none\"])');
                            if (button) {
                                button.click();
                            } else {
                                // Fallback: try to trigger via Livewire
                                \$wire.mountAction('create_referral');
                            }
                        }, 50);
                    ");
                })
                ->visible(fn() => in_array(self::currentUser()->role, [RoleEnum::ADMIN, RoleEnum::BHW])),
                
            // Second action: The actual form modal for creating referral
            Actions\Action::make('create_referral')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->visible(fn() => true)
                ->extraAttributes(['style' => 'display: none !important;']) // Hide with CSS
                ->modalHeading(fn($record) => $record->referral
                    ? "Referral Details for {$record->patient->first_name} {$record->patient->last_name}"
                    : "Creating a referral for {$record->patient->first_name} {$record->patient->last_name}"
                )
                ->requiresConfirmation(false)
                ->modalWidth('md')
                ->slideOver()
                ->form(function ($record) {
                    session()->forget('referral_confirmed_for_' . $record->id);

                    return [
                        Select::make('referred_to')
                            ->options([
                                'Carmen MHO' => 'Carmen MHO',
                            ])
                            ->default('Carmen MHO')
                            ->required(),

                        Radio::make('referral_reason')
                            ->options([
                                'Hospital Capability'  => 'Hospital Capability',
                                'Lack of Specialists'  => 'Lack of Specialists',
                                'Financial Constraint' => 'Financial Constraint',
                                'Other'                => 'Other',
                            ])
                            ->columns(2)
                            ->live()
                            ->required()
                            ->gridDirection('row'),

                        Textarea::make('reason_for_referral_other')
                            ->label('State your reason for referral')
                            ->hidden(fn(Get $get) => ! $get('referral_reason') || $get('referral_reason') !== 'Other')
                            ->required(fn(Get $get) => $get('referral_reason') === 'Other')
                            ->maxLength(65535),

                        ToggleButtons::make('urgency')
                            ->required()
                            ->options([
                                'Emergency'    => 'Emergency',
                                'Ambulatory'   => 'Ambulatory',
                                'Medico-Legal' => 'Medico-Legal',
                            ])
                            ->inline(),

                        ToggleButtons::make('surgical_operation')
                            ->boolean()
                            ->default(false)
                            ->live()
                            ->inline(),

                        TextInput::make('procedure')
                            ->label('What Procedure?')
                            ->hidden(fn(Get $get) => ! $get('surgical_operation'))
                            ->required(fn(Get $get) => $get('surgical_operation')),

                        ToggleButtons::make('drug_allergy')
                            ->boolean()
                            ->default(false)
                            ->live()
                            ->inline(),

                        TextInput::make('drug_allergy_notes')
                            ->label('What Allergy?')
                            ->hidden(fn(Get $get) => ! $get('drug_allergy'))
                            ->required(fn(Get $get) => $get('drug_allergy')),

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

                        Placeholder::make('Note')
                            ->label('Note:')
                            ->columnSpanFull()
                            ->content('Referring Facility to retain a duplicate copy of Clinical Referral Form for Record Purposes and Data Profiling; Please attach laboratory work-ups.'),
                    ];
                })
                ->action(function ($record, array $data) {
                    try {
                        // Generate custom referral ID
                        $currentDate = now();
                        $dateFormat  = $currentDate->format('Ymd');
                        $brgyId      = $record->patient->barangay_id; // Assuming patient has brgy_id

                        // Get the next auto-incremented number for today
                        $todaysCount   = \App\Models\Referral::whereDate('created_at', $currentDate->toDateString())->count();
                        $autoIncrement = str_pad($todaysCount + 1, 3, '0', STR_PAD_LEFT);

                        // You can customize the prefix as needed
                        $prefix = 'REF'; // Change this to your desired prefix

                        // Generate ref_id: PREFIX-YYYY-MM-DD-001-BRGY_ID
                        $refId = "{$prefix}-{$dateFormat}-{$autoIncrement}-{$brgyId}";

                        // Create the referral record
                        $referral = \App\Models\Referral::create([
                            'ref_id'                    => $refId,
                            'consultation_id'           => $record->id,
                            'patient_id'                => $record->patient_id,
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
                            'created_at'                => now(),
                            'status'                    => 'pending', // Default status
                        ]);

                        // Optional: Update the original record status if needed
                        // $record->update(['status' => 'referred']);

                        Notification::make()
                            ->title('Referral created successfully')
                            ->body("Referral #{$referral->id} has been created for {$record->patient->first_name} {$record->patient->last_name}")
                            ->success()
                            ->send();

                        // redirect to consultation current page
                        redirect()->back();

                    } catch (\Exception $e) {
                        logger()->error('Error creating referral: ' . $e->getMessage());

                        Notification::make()
                            ->title('Error creating referral')
                            ->body('Please try again or contact support if the problem persists.')
                            ->danger()
                            ->send();
                    }
                }),
                
            Actions\Action::make('view_referral')
                ->icon('heroicon-o-eye')
                ->label('View Previous Referrals')
                ->color('gray')
                ->visible(fn($record) => $record->referral()->exists()) // Check if referrals exist
                ->slideOver()
                ->modalHeading(fn($record) => "All Referrals for {$record->patient->first_name} {$record->patient->last_name}")
                ->form(function ($record) {
                    // Get all referrals as a collection
                    $referrals = $record->referral()->get();
                    
                    return $referrals->map(function ($referral, $index) {
                        return Section::make('Referral #' . ($index + 1) . ' (' . $referral->created_at->format('M d, Y h:i A') . ')')
                            ->schema([
                                TextInput::make("ref_id_{$referral->id}")
                                    ->label('Referral ID')
                                    ->default($referral->ref_id)
                                    ->disabled(),

                                TextInput::make("referred_to_{$referral->id}")
                                    ->label('Referred To')
                                    ->default($referral->referred_to)
                                    ->disabled(),

                                TextInput::make("referral_reason_{$referral->id}")
                                    ->label('Referral Reason')
                                    ->default($referral->referral_reason)
                                    ->disabled(),

                                Textarea::make("reason_for_referral_other_{$referral->id}")
                                    ->label('Other Reason')
                                    ->default($referral->reason_for_referral_other)
                                    ->disabled()
                                    ->hidden(fn() => empty($referral->reason_for_referral_other)),

                                TextInput::make("urgency_{$referral->id}")
                                    ->label('Urgency')
                                    ->default($referral->urgency)
                                    ->disabled(),

                                TextInput::make("surgical_operation_{$referral->id}")
                                    ->label('Surgical Operation')
                                    ->default($referral->surgical_operation ? 'Yes' : 'No')
                                    ->disabled(),

                                TextInput::make("procedure_{$referral->id}")
                                    ->label('Procedure')
                                    ->default($referral->procedure)
                                    ->disabled()
                                    ->hidden(fn() => empty($referral->procedure)),

                                TextInput::make("drug_allergy_{$referral->id}")
                                    ->label('Drug Allergy')
                                    ->default($referral->drug_allergy ? 'Yes' : 'No')
                                    ->disabled(),

                                TextInput::make("drug_allergy_notes_{$referral->id}")
                                    ->label('Allergy Notes')
                                    ->default($referral->drug_allergy_notes)
                                    ->disabled()
                                    ->hidden(fn() => empty($referral->drug_allergy_notes)),

                                Textarea::make("chief_complaint_{$referral->id}")
                                    ->label('Chief Complaint')
                                    ->default($referral->chief_complaint)
                                    ->disabled()
                                    ->hidden(fn() => empty($referral->chief_complaint)),

                                Textarea::make("action_taken_{$referral->id}")
                                    ->label('Action Taken')
                                    ->default($referral->action_taken)
                                    ->disabled()
                                    ->hidden(fn() => empty($referral->action_taken)),

                                Textarea::make("impression_{$referral->id}")
                                    ->label('Impression')
                                    ->default($referral->impression)
                                    ->disabled()
                                    ->hidden(fn() => empty($referral->impression)),

                                Textarea::make("hpi_notes_{$referral->id}")
                                    ->label('HPI Notes')
                                    ->default($referral->hpi_notes)
                                    ->disabled()
                                    ->hidden(fn() => empty($referral->hpi_notes)),

                                Textarea::make("notes_{$referral->id}")
                                    ->label('Additional Notes')
                                    ->default($referral->notes)
                                    ->disabled()
                                    ->hidden(fn() => empty($referral->notes)),

                                TextInput::make("created_by_{$referral->id}")
                                    ->label('Created By')
                                    ->default($referral->user->name ?? 'Unknown')
                                    ->disabled(),

                                TextInput::make("status_{$referral->id}")
                                    ->label('Status')
                                    ->default(ucfirst($referral->status))
                                    ->disabled(),
                            ])
                            ->columns(2);
                    })->toArray();
                })
                ->modalCancelActionLabel('Close')
                ->modalSubmitAction(false),
        ];
    }
        
    protected function currentUser(): ?User
    {
        return Auth::user();
    }
}