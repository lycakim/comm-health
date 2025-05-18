<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Referral;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Consultation;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ReferralResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ReferralResource\RelationManagers;

class ReferralResource extends Resource
{
    protected static ?string $model = Referral::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 3;

    // protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Referral Information')
                    ->schema([
                        Select::make('consultation_id')
                            ->label('Select Consultation')
                            ->options(
                                Consultation::with('patient')
                                    ->get()
                                    ->mapWithKeys(function ($consultation) {
                                        
                                        $patientName = $consultation->patient 
                                            ? "Consultation #{$consultation->id} - {$consultation->patient->first_name} {$consultation->patient->last_name} - {$consultation->created_at->format('M d, Y')}"
                                            : 'Unknown';
                                        return [$consultation->id => $patientName];
                                    })
                                    ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        
                        Select::make('barangay_id')
                            ->relationship('barangay', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Section::make('Patient Information')
                    ->schema([
                        Forms\Components\Placeholder::make('patient_name')
                            ->label('Patient Name')
                            ->content(function (Forms\Get $get) {
                                $consultationId = $get('consultation_id');
                                if (!$consultationId) return '-';
                                
                                $consultation = Consultation::find($consultationId);
                                return $consultation ? $consultation->patient->first_name . ' ' . $consultation->patient->last_name : '-';
                            }),
                        Forms\Components\Placeholder::make('chief_complaint')
                            ->label('Chief Complaint')
                            ->content(function (Forms\Get $get) {
                                $consultationId = $get('consultation_id');
                                if (!$consultationId) return '-';
                                
                                $consultation = Consultation::find($consultationId);
                                return $consultation ? $consultation->chief_complaint : '-';
                            }),
                        Forms\Components\Placeholder::make('diagnosis')
                            ->label('Diagnosis')
                            ->content(function (Forms\Get $get) {
                                $consultationId = $get('consultation_id');
                                if (!$consultationId) return '-';
                                
                                $consultation = Consultation::find($consultationId);
                                return $consultation && $consultation->diagnosis ? $consultation->diagnosis : 'Not provided';
                            }),
                    ])->columns(3)
                    ->visible(fn (Forms\Get $get) => (bool) $get('consultation_id')),

                Section::make('Referral Details')
                    ->schema([
                        Forms\Components\TextInput::make('facility_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('facility_address')
                            ->maxLength(65535),
                        Forms\Components\Select::make('urgency')
                            ->options([
                                'routine' => 'Routine',
                                'urgent' => 'Urgent',
                                'emergency' => 'Emergency',
                            ])
                            ->default('routine')
                            ->required(),
                        Forms\Components\Textarea::make('reason_for_referral')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('referring_provider_notes')
                            ->label('Additional Notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('date_referred')
                            ->default(Carbon::now())
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'accepted' => 'Accepted',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required(),
                    ])->columns(2),

                Section::make('Recipient Information')
                    ->schema([
                        Forms\Components\TextInput::make('receiving_provider_name')
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('date_completed'),
                        Forms\Components\Textarea::make('receiving_provider_notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2)
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
                Tables\Columns\TextColumn::make('consultation.patient')
                    ->label('Patient')
                    ->formatStateUsing(fn ($state) => $state ? "{$state->first_name} {$state->last_name}" : '-')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('patient', function (Builder $query) use ($search) {
                            $query->where('first_name', 'like', "%{$search}%")
                                  ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('facility_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('barangay.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_referred')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('urgency')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'routine' => 'info',
                        'urgent' => 'warning',
                        'emergency' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'accepted' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Referred By')
                    ->sortable(),
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