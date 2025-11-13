<?php

namespace App\Filament\Resources\ConsultationResource\RelationManagers;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Enums\RoleEnum;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use App\Services\PDFGenerationService;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class ReferralsRelationManager extends RelationManager
{
    protected static string $relationship = 'referral';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('consultation_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('referral_id')
            ->columns([
                Tables\Columns\TextColumn::make('ref_id')->label('Referral ID'),
                Tables\Columns\TextColumn::make('referred_to')->label('Referred To'),
                Tables\Columns\TextColumn::make('referral_reason')->label('Referral Reason'),
                Tables\Columns\TextColumn::make('created_at')->label('Created At')->dateTime('M j, Y'),   
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('print_referral')
                    ->icon('heroicon-o-printer')
                    ->label('Print')
                    ->color('primary')
                    ->action(function ($record) {
                        $patient = $record->patient;
                        $consultation = $record->consultation;
                        $pdfService = new PDFGenerationService();
                        
                        $pdf = $pdfService->generateReferralPdf($record, $patient, $consultation);
                        
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, "referral-{$record->ref_id}.pdf");
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function currentUser(): ?User
    {
        return Auth::user();
    }
}