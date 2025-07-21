<?php
namespace App\Filament\Resources;

use App\Enums\RoleEnum;
use App\Filament\Resources\ConsultationResource\Pages;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\User;
use App\Services\ConsultationFormService;
use App\Traits\HasUserTypeUrls;
use Filament\Forms;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ConsultationResource extends Resource
{
    use HasUserTypeUrls;

    protected static ?string $model = Consultation::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    // protected static ?int $navigationSort = 2;

    public static function getNavigationSort(): ?int
    {
        if (self::currentUser()->isMHO()) {
            return 3;
        }
        return 2;
    }

    public static function canAccess(): bool
    {
        return in_array(self::currentUser()->role, [
            RoleEnum::ADMIN,
            RoleEnum::MHO,
            RoleEnum::BHW,
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(ConsultationFormService::handle());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient_full_name')
                    ->label('Patient Name')
                    ->getStateUsing(fn($record) => $record->patient->first_name . ' ' . $record->patient->last_name)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->with('patient')
                            ->where('id', 'like', "%{$search}%")
                            ->orWhereHas('patient', function (Builder $patientQuery) use ($search) {
                                $patientQuery->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%");
                            });
                    }),
                Tables\Columns\TextColumn::make('date')
                    ->dateTime()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->label('Created By')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'completed'      => 'Completed',
                        'needs_referral' => 'Needs Referral',
                        'referred'       => 'Referred',
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
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->color('warning'),
                Tables\Actions\Action::make('create_referral')
                    ->label(fn($record) => $record->referral ? 'Referred' : 'Create Referral')
                    ->icon(fn($record) => $record->referral ? 'heroicon-o-eye' : 'heroicon-o-plus')
                    ->color(fn($record) => $record->referral ? 'info' : 'success')
                    ->modalHeading(fn($record) => $record->referral
                        ? "Referral Details for {$record->patient->first_name} {$record->patient->last_name}"
                        : "Creating a referral for {$record->patient->first_name} {$record->patient->last_name}"
                    )
                    ->requiresConfirmation(false)
                    ->modalWidth('md')
                    ->slideOver()
                    ->visible(fn() => in_array(self::currentUser()->role, [
                        RoleEnum::ADMIN,
                        RoleEnum::BHW,
                    ]))
                    ->form(function ($record) {
                        // If referral exists, show details (read-only)
                        if ($record->referral) {
                            return [
                                Forms\Components\Section::make('Referral Information')
                                    ->schema([
                                        Forms\Components\TextInput::make('referral_id')
                                            ->label('Referral ID')
                                            ->default($record->referral->ref_id)
                                            ->disabled()
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('referral_date')
                                            ->label('Referral Date')
                                            ->default($record->referral->created_at->format('M d, Y h:i A'))
                                            ->disabled(),

                                        Forms\Components\TextInput::make('referred_to')
                                            ->label('Referred To')
                                            ->default($record->referral->referred_to)
                                            ->disabled(),

                                        Forms\Components\TextInput::make('referral_reason')
                                            ->label('Referral Reason')
                                            ->default($record->referral->referral_reason)
                                            ->disabled(),

                                        Forms\Components\Textarea::make('reason_for_referral_other')
                                            ->label('Other Reason')
                                            ->default($record->referral->reason_for_referral_other)
                                            ->disabled()
                                            ->hidden(fn() => ! $record->referral->reason_for_referral_other),

                                        Forms\Components\TextInput::make('urgency')
                                            ->label('Urgency')
                                            ->default($record->referral->urgency)
                                            ->disabled(),

                                        Forms\Components\TextInput::make('surgical_operation')
                                            ->label('Surgical Operation')
                                            ->default($record->referral->surgical_operation ? 'Yes' : 'No')
                                            ->disabled(),

                                        Forms\Components\TextInput::make('procedure')
                                            ->label('Procedure')
                                            ->default($record->referral->procedure)
                                            ->disabled()
                                            ->hidden(fn() => ! $record->referral->procedure),

                                        Forms\Components\TextInput::make('drug_allergy')
                                            ->label('Drug Allergy')
                                            ->default($record->referral->drug_allergy ? 'Yes' : 'No')
                                            ->disabled(),

                                        Forms\Components\TextInput::make('drug_allergy_notes')
                                            ->label('Allergy Notes')
                                            ->default($record->referral->drug_allergy_notes)
                                            ->disabled()
                                            ->hidden(fn() => ! $record->referral->drug_allergy_notes),

                                        Forms\Components\Textarea::make('chief_complaint')
                                            ->label('Chief Complaint')
                                            ->default($record->referral->chief_complaint)
                                            ->disabled()
                                            ->hidden(fn() => ! $record->referral->chief_complaint),

                                        Forms\Components\Textarea::make('action_taken')
                                            ->label('Action Taken')
                                            ->default($record->referral->action_taken)
                                            ->disabled()
                                            ->hidden(fn() => ! $record->referral->action_taken),

                                        Forms\Components\Textarea::make('impression')
                                            ->label('Impression')
                                            ->default($record->referral->impression)
                                            ->disabled()
                                            ->hidden(fn() => ! $record->referral->impression),

                                        Forms\Components\Textarea::make('hpi_notes')
                                            ->label('HPI Notes')
                                            ->default($record->referral->hpi_notes)
                                            ->disabled()
                                            ->hidden(fn() => ! $record->referral->hpi_notes),

                                        Forms\Components\Textarea::make('notes')
                                            ->label('Additional Notes')
                                            ->default($record->referral->notes)
                                            ->disabled()
                                            ->hidden(fn() => ! $record->referral->notes),

                                        Forms\Components\TextInput::make('created_by')
                                            ->label('Created By')
                                            ->default($record->referral->user->name ?? 'Unknown')
                                            ->disabled(),

                                        Forms\Components\TextInput::make('status')
                                            ->label('Status')
                                            ->default(ucfirst($record->referral->status))
                                            ->disabled(),
                                    ])
                                    ->columns(2),
                            ];
                        }

                        // If no referral exists, show creation form
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
                                ->hidden(fn(Forms\Get $get) => ! $get('referral_reason') || $get('referral_reason') !== 'Other')
                                ->required(fn(Forms\Get $get) => $get('referral_reason') === 'Other')
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
                                ->hidden(fn(Forms\Get $get) => ! $get('surgical_operation'))
                                ->required(fn(Forms\Get $get) => $get('surgical_operation')),

                            ToggleButtons::make('drug_allergy')
                                ->boolean()
                                ->default(false)
                                ->live()
                                ->inline(),

                            TextInput::make('drug_allergy_notes')
                                ->label('What Allergy?')
                                ->hidden(fn(Forms\Get $get) => ! $get('drug_allergy'))
                                ->required(fn(Forms\Get $get) => $get('drug_allergy')),

                            Forms\Components\Textarea::make('chief_complaint')
                                ->maxLength(65535),
                            Forms\Components\Textarea::make('action_taken')
                                ->maxLength(65535),
                            Forms\Components\Textarea::make('impression')
                                ->maxLength(65535),
                            Forms\Components\Textarea::make('hpi_notes')
                                ->maxLength(65535),

                            Textarea::make('notes')
                                ->label('Additional Notes')
                                ->rows(3)
                                ->maxLength(1000),

                            Forms\Components\Placeholder::make('Note')
                                ->label('Note:')
                                ->columnSpanFull()
                                ->content('Referring Facility to retain a duplicate copy of Clinical Referral Form for Record Purposes and Data Profiling; Please attach laboratory work-ups.'),
                        ];
                    })
                    ->action(function ($record, array $data) {
                        // If referral already exists, don't create another one
                        if ($record->referral) {
                            return;
                        }

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

                            // redirect to consultatation current page
                            redirect()->back();

                        } catch (\Exception $e) {
                            logger()->error('Error creating referral: ' . $e->getMessage());

                            Notification::make()
                                ->title('Error creating referral')
                                ->body('Please try again or contact support if the problem persists.')
                                ->danger()
                                ->send();
                        }
                    })
                    ->after(function () {
                        // Explicitly prevent any table refresh
                        // This ensures the table state remains intact
                    })
                    ->modalSubmitAction(fn($record) => $record->referral ? false : null)
                    ->modalCancelActionLabel(fn($record) => $record->referral ? 'Close' : 'Cancel'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->groups([
                'date',
                'patient.first_name',
                Tables\Grouping\Group::make('referral_status')
                    ->label('Referral Status')
                    ->getDescriptionFromRecordUsing(function ($record) {
                        return $record->referral ? 'Referred' : 'Not Referred';
                    })
                    ->orderQueryUsing(function (Builder $query, string $direction) {
                        return $query->orderByRaw('(SELECT COUNT(*) FROM referrals WHERE referrals.consultation_id = consultations.id) ' . $direction);
                    }),
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
            'index'  => Pages\IndexConsultations::route('/'),
            'all'    => Pages\AllConsultations::route('/all'),
            'list'   => Pages\ListConsultations::route('/list/{barangay?}'),
            'create' => Pages\CreateConsultation::route('/create'),
            'edit'   => Pages\EditConsultation::route('/{record}/edit'),
        ];
    }

    // initialize auth user
    public static function currentUser(): ?User
    {
        return Auth::user();
    }
}