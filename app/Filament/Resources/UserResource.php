<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Purok;
use App\Enums\RoleEnum;
use Filament\Forms\Get;
use App\Models\Barangay;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\RelationManagers;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Management';

    protected static ?int $navigationSort = 1;

    public static function getNavigationSort(): ?int
    {
        if (self::currentUser()->isMHO()) {
            return 1;
        }
        return 1;
    }

    public static function getPluralModelLabel(): string
    {
        if (self::currentUser()->isMHO()) {
            return 'User Management';
        }
        return 'Users';
    }

    public static function canAccess(): bool
    {
        return in_array(self::currentUser()->role, [
            RoleEnum::ADMIN,
            RoleEnum::MHO,
        ]);
    }

    public static function canEdit(Model $request): bool
    {
        $user = self::currentUser();
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
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('email')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->columnSpanFull()
                            ->minLength(8)
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->columnSpanFull(),
                        Select::make('role')
                            ->live()
                            ->searchable()
                            ->options([
                                'bhw' => 'Barangay Health Worker',
                                'mho' => 'Municipal Health Officer',
                                'midwife' => 'Midwife',
                            ])
                            ->default('bhw')
                            ->in(['mho', 'bhw', 'resident', 'midwife', 'admin'])
                            ->required()
                            ->columnSpanFull(),
                        Select::make('barangay_id')
                            ->label('Assigned Barangay')
                            ->options(Barangay::query()->get()->pluck('name', 'id')->sort()->toArray())
                            ->preload()
                            ->live()
                            ->searchable(),
                        Select::make('purok_id')
                            ->label('Purok')
                            ->searchable()
                            ->disabled(fn (Get $get) => ! $get('barangay_id'))
                            ->options(function (Get $get) {
                                $barangayId = $get('barangay_id');
                                return Purok::query()->where('barangay_id', $barangayId)->get()->pluck('name', 'id')->sort()->toArray();
                            })
                            ->preload()
                            ->createOptionForm(function (Get $get) {
                                $barangayId = $get('barangay_id');
                                
                                return [
                                    TextInput::make('name')
                                        ->unique('puroks', 'name', modifyRuleUsing: function ($rule, Get $get) {
                                            $barangayId = $get('barangay_id');
                                            return $rule->where('barangay_id', $barangayId);
                                        })
                                        ->required(),
                                    Select::make('barangay_id')
                                        ->label('Barangay')
                                        ->required()
                                        ->options(function () use ($barangayId) {
                                            if (!$barangayId) {
                                                return [];
                                            }
                                            
                                            return Barangay::query()
                                                ->where('id', $barangayId)
                                                ->get()
                                                ->pluck('name', 'id')
                                                ->toArray();
                                        })
                                        ->default($barangayId)
                                        ->disabled(),
                                ];
                            })
                            ->createOptionUsing(function (array $data, Get $get): int {
                                $barangayId = $get('barangay_id');

                                $data['barangay_id'] = $barangayId;
                                
                                return Purok::create($data)->getKey();
                            }),
                    ])
                    ->columns(2),
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
                SelectColumn::make('barangay_id')
                    ->label('Assigned Barangay')
                    ->searchable()
                    ->sortable()
                    ->disabled(fn ($record) => $record->isAdmin() || $record->isMHO())
                    ->options(Barangay::query()->get()->pluck('name', 'id')->sort()->toArray())
                    ->getStateUsing(fn ($record) => $record->barangay_id)
                    ->updateStateUsing(function ($record, $state) {
                        $record->barangay_id = $state;
                        $record->save();
                        return $state;
                    }),
                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->getStateUsing(fn ($record) => $record->is_active)
                    ->updateStateUsing(function ($record, $state) {
                        $record->is_active = $state;
                        $record->save();
                        return $state;
                    }),
                TextColumn::make('role')
                    ->formatStateUsing(fn (RoleEnum $state) => $state->getLabel())
                    ->badge()
                    ->color(fn (RoleEnum $state): string => $state->getColor()),
                
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
                $query->latest();
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    // initialize auth user
    public static function currentUser(): ?User
    {
        return Auth::user();
    }
}