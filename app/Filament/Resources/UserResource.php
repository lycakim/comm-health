<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Enums\RoleEnum;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\RelationManagers;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Management';

    // protected static ?int $navigationSort = 1;

    public static function getNavigationSort(): ?int
    {
        if (auth()->user()->isMHO()) {
            return 1;
        }
        return 1;
    }

    public static function getPluralModelLabel(): string
    {
        if (auth()->user()->isMHO()) {
            return 'User Management';
        }
        return 'Users';
    }

    public static function canAccess(): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isMHO();
    }

    public static function canEdit(Model $request): bool
    {
        $user = auth()->user();
        return $user->isAdmin() || $user->isMHO() || $user->isBHW() || $user->isMidwife();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->columnSpanFull()
                            ->minLength(8)
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),
                        Select::make('role')
                            ->visible(auth()->user()->isAdmin())
                            ->live()
                            ->options([
                                'bhw' => 'Barangay Health Worker',
                                'mho' => 'Municipal Health Officer',
                                'resident' => 'Resident',
                                'midwife' => 'Midwife',
                                'admin' => 'Admin',
                            ])
                            ->default('admin')
                            ->in(['mho', 'bhw', 'resident', 'midwife', 'admin'])
                            ->required(),
                        Select::make('barangay_id')
                            ->label('Assigned Barangay')
                            ->relationship('barangays', 'name')
                            ->preload()
                            ->searchable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('barangays.name')
                    ->label('Assigned Barangay')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->formatStateUsing(fn ($state) => RoleEnum::tryFrom($state)?->getLabel() ?? ucfirst($state))
                    ->badge()
                    ->color(fn (string $state): string => RoleEnum::tryFrom($state)?->getColor() ?? 'gray'),
                
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}