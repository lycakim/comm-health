<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\PersonType;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PersonTypeResource\Pages;
use App\Filament\Resources\PersonTypeResource\RelationManagers;

class PersonTypeResource extends Resource
{
    protected static ?string $model = PersonType::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function getNavigationGroup(): ?string
    {
        if (auth()->user()->isBHW() || auth()->user()->isMidwife()) {
            return 'Programs';
        }
        return 'Utility';
    }

    public static function getNavigationSort(): ?int
    {
        if (auth()->user()->isMHO()) {
            return 2;
        }
        else if (auth()->user()->isBHW() || auth()->user()->isMidwife()) {
            return 1;
        }
        
        return 5;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('name')->required(),
                        Textarea::make('description'),
                        ToggleButtons::make('is_active')
                            ->label('Is Active?')
                            ->boolean()
                            ->default(true)
                            ->grouped()
                            ->inline(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
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
            'index' => Pages\ListPersonTypes::route('/'),
            'create' => Pages\CreatePersonType::route('/create'),
            'edit' => Pages\EditPersonType::route('/{record}/edit'),
        ];
    }
}