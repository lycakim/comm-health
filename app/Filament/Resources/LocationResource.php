<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Enums\RoleEnum;
use App\Models\Location;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\LocationResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\LocationResource\RelationManagers;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Management';

    public static function canAccess(): bool
    {
        return in_array(self::currentUser()->role, [
            RoleEnum::ADMIN,
            RoleEnum::MHO,
            RoleEnum::BHW,
            RoleEnum::MIDWIFE
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('type')
                            ->label('Type')
                            ->options([
                                'province' => 'Province',
                                'city' => 'City',
                                'municipality' => 'Municipality',
                                'barangay' => 'Barangay',
                                'purok' => 'Purok',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('parent_id', null)),
        
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
        
                        Select::make('parent_id')
                            ->label('Parent Location')
                            ->options(function (callable $get) {
                                $type = $get('type');

                                return match ($type) {
                                    'city', 'municipality' => Location::where('type', 'province')
                                        ->orderBy('name')
                                        ->pluck('name', 'id'),

                                    'barangay' => Location::whereIn('type', ['city', 'municipality'])
                                        ->with('parent') // parent = province
                                        ->get()
                                        ->mapWithKeys(function ($loc) {
                                            $province = $loc->parent?->name;
                                            $hint = $province ? " ({$province})" : '';
                                            return [$loc->id => "{$loc->name}{$hint}"];
                                        }),

                                    // Purok → parent is barangay (show city + province hints)
                                    'purok' => Location::where('type', 'barangay')
                                        ->with(['parent.parent']) // parent = city/municipality, parent's parent = province
                                        ->get()
                                        ->mapWithKeys(function ($loc) {
                                            $city = $loc->parent?->name;
                                            $province = $loc->parent?->parent?->name;
                                            $hints = array_filter([$city, $province]);
                                            $hintText = $hints ? ' (' . implode(', ', $hints) . ')' : '';
                                            return [$loc->id => "{$loc->name}{$hintText}"];
                                        }),

                                    default => collect(),
                                };
                            })
                            ->searchable()
                            ->visible(fn (callable $get) => $get('type') !== 'province')
                            ->required(fn (callable $get) => $get('type') !== 'province'),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Name')->searchable()->sortable(),
                TextColumn::make('type')
                    ->colors([
                        'success' => 'province',
                        'info' => 'city',
                        'warning' => 'municipality',
                        'primary' => 'barangay',
                        'gray' => 'purok',
                    ])
                    ->badge(),
                TextColumn::make('parent.name')->label('Parent')->sortable()->searchable(),
                TextColumn::make('created_at')->dateTime('M d, Y')->label('Created'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Filter by Type')
                    ->options([
                        'province' => 'Province',
                        'city' => 'City',
                        'municipality' => 'Municipality',
                        'barangay' => 'Barangay',
                        'purok' => 'Purok',
                    ]),
            ])
            ->defaultSort('type', 'asc')
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
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }

    // initialize auth user
    public static function currentUser(): ?User
    {
        return Auth::user();
    }
}