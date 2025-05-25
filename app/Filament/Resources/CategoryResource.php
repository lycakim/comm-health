<?php

namespace App\Filament\Resources;

use Dom\Text;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use App\Models\Category;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use Filament\Resources\Resource;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ToggleButtons;
use App\Filament\Resources\CategoryResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\CategoryResource\RelationManagers;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Utility';

    protected static ?int $navigationSort = 3;
    
    public static function canAccess(): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isMHO();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->rules(function ($record) {
                                return [
                                    Rule::unique('categories', 'name')->ignore($record?->id),
                                ];
                            }),
                        Textarea::make('description'),
                        ToggleButtons::make('is_child')
                            ->label('Is Child Category?')
                            ->live()
                            ->disabled(fn (Get $get) => $get('is_maternal'))
                            ->grouped()
                            ->boolean(),
                        ToggleButtons::make('is_maternal')
                            ->live()
                            ->disabled(fn (Get $get) => $get('is_child'))
                            ->label('Is Maternal Category?')
                            ->grouped()
                            ->boolean()
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}