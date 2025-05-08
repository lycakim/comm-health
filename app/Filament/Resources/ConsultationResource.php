<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Patient;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Consultation;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\RichEditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ConsultationResource\Pages;
use App\Filament\Resources\ConsultationResource\RelationManagers;

class ConsultationResource extends Resource
{
    protected static ?string $model = Consultation::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('patient_id')
                            ->label('Patient')
                            ->options(function () {
                                return Patient::query()
                                    ->get()
                                    ->mapWithKeys(function ($patient) {
                                        return [$patient->id => $patient->first_name . ' ' . $patient->last_name];
                                    })
                                    ->toArray();
                            })
                            ->required(),
                        Select::make('consultation_type')
                            ->options([
                                'Prenatal' => 'Prenatal',
                                'Postnatal' => 'Postnatal',
                                'Immunization' => 'Immunization',
                                'Chronic Disease' => 'Chronic Disease',
                            ])
                            ->required(),
                        Select::make('consultation_program')
                            ->options([
                                'Prenatal' => 'Prenatal',
                                'Postnatal' => 'Postnatal',
                                'Immunization' => 'Immunization',
                                'Chronic Disease' => 'Chronic Disease',
                            ])
                            ->required(),
                        RichEditor::make('notes')
                            ->toolbarButtons([
                                'attachFiles',
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ])
                    ])
                    ->columnSpan(2),
                Section::make()
                    ->schema([
                        ViewField::make('rating')
                            ->dehydrated(false)
                            ->view('filament.forms.components.consultation-stats')
                            ->viewData([
                                'totalPatients' => 12,
                                'maternalCount' => 5,
                                'childCount' => 5,
                                'seniorCount' => 4,
                                'chronicCount' => 1,
                                'recentActivities' => 0,
                            ])
                    ])
                    ->columnSpan(1),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListConsultations::route('/'),
            'create' => Pages\CreateConsultation::route('/create'),
            'edit' => Pages\EditConsultation::route('/{record}/edit'),
        ];
    }
}