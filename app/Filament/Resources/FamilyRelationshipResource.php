<?php

namespace App\Filament\Resources;

use App\Enums\RoleEnum;
use App\Models\FamilyRelationship;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Filament\Resources\FamilyRelationshipResource\Pages;

class FamilyRelationshipResource extends Resource
{
    protected static ?string $model = FamilyRelationship::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Utility';

    protected static ?string $navigationLabel = 'Family Relationships';

    protected static ?int $navigationSort = 5;

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
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Relationship Name')
                            ->required()
                            ->maxLength(100)
                            ->rules(fn ($record) => [
                                Rule::unique('family_relationships', 'name')->ignore($record?->id),
                            ])
                            ->helperText('e.g. Spouse, Child, Sibling'),
                        Hidden::make('user_id')
                            ->default(fn () => Auth::id()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Relationship Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFamilyRelationships::route('/'),
            'create' => Pages\CreateFamilyRelationship::route('/create'),
            'edit'   => Pages\EditFamilyRelationship::route('/{record}/edit'),
        ];
    }

    public static function currentUser(): ?User
    {
        return Auth::user();
    }
}
