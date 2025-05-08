<?php

namespace App\Filament\Resources;

use Dom\Text;
use Carbon\Carbon;
use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Patient;
use Filament\Forms\Get;
use App\Models\Barangay;
use App\Models\Category;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Checkbox;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ToggleButtons;
use App\Filament\Resources\PatientResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PatientResource\RelationManagers;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                // TextInput::make('first_name')->required(),
                                // TextInput::make('last_name')->required(),
                                Select::make('resident_id')
                                    ->label('Resident')
                                    ->options(User::query()->get()->pluck('name', 'id')->toArray())
                                    ->columnSpanFull(),
                                TextInput::make('middle_name')
                                    ->required()
                                    ->hidden(fn (Get $get): bool => !$get('add_middle_name'))
                                    ->columnSpanFull(),
                                DatePicker::make('birth_date')->required(),
                                Select::make('sex')
                                    ->options([
                                        'male' => 'Male',
                                        'female' => 'Female',
                                    ])
                                    ->required(),
                                Select::make('category_id')
                                    ->options(Category::query()->get()->pluck('name', 'id')->toArray())
                                    ->required()
                                    ->columnSpanFull(),
                                Select::make('barangay_id')
                                    ->options(Barangay::query()->get()->pluck('name', 'id')->toArray())
                                    ->required(),
                                DatePicker::make('last_visit')->required(),
                                ToggleButtons::make('surgical_operation')
                                    ->grouped()
                                    ->boolean()
                                    ->inline(),
                                ToggleButtons::make('add_middle_name')
                                    ->label('Add middle name?')
                                    ->dehydrated(false)
                                    ->grouped()
                                    ->boolean()
                                    ->inline()
                                    ->default(false)  
                                    ->live(),
                            ]),
                    ])
                    ->columnSpan(2),
                Section::make()
                    ->schema([
                        ViewField::make('rating')
                            ->dehydrated(false)
                            ->view('filament.forms.components.patient-stats')
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
                TextColumn::make('full_name')
                    ->label('Full Name')
                    ->searchable()
                    ->searchable(['first_name', 'last_name'])
                    ->getStateUsing(function ($record) {
                        return $record->first_name . ' ' . $record->last_name;
                    }),
                TextColumn::make('sex')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return ucfirst($state);
                    })
                    ->color(fn (string $state): string => match ($state) {
                        default => 'gray',
                    }),
                TextColumn::make('age')
                    ->getStateUsing(function ($record) {
                        return Carbon::parse($record->birth_date)->age;
                    })
                    ->label('Age'),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable(),
                TextColumn::make('barangay.name')
                    ->label('Barangay')
                    ->searchable(),
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
            ])
            ->modifyQueryUsing(function (Builder $query) {
                return $query->latest();
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
            'index' => Pages\ListPatients::route('/'),
            'create' => Pages\CreatePatient::route('/create'),
            'edit' => Pages\EditPatient::route('/{record}/edit'),
        ];
    }
}