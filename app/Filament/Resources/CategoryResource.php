<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Enums\RoleEnum;
use Filament\Forms\Get;
use App\Models\Category;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Traits\HasUserTypeUrls;
use Illuminate\Validation\Rule;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
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
    use HasUserTypeUrls;

    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Utility';

    protected static ?int $navigationSort = 3;
    
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
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->rules(function ($record) {
                                return [
                                    Rule::unique('categories', 'name')->ignore($record?->id),
                                ];
                            }),
                        TextInput::make('age_min')
                            ->label('Age range (min years)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(150)
                            ->helperText('Optional. Min age (years) for auto-assignment. Leave empty for no lower bound.')
                            ->placeholder('e.g. 0'),
                        TextInput::make('age_min_months')
                            ->label('Age range (min months)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(11)
                            ->helperText('Optional. Additional months for min age (0-11).')
                            ->placeholder('e.g. 6'),
                        TextInput::make('age_max')
                            ->label('Age range (max years)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(150)
                            ->helperText('Optional. Max age (years). Leave empty for no upper bound (e.g. 60+).')
                            ->placeholder('e.g. 2')
                            ->rules(fn (Get $get) => [
                                Rule::when(
                                    $get('age_min') !== null && $get('age_min') !== '' && $get('age_max') !== null && $get('age_max') !== '',
                                    'gte:' . (int) $get('age_min')
                                ),
                            ]),
                        TextInput::make('age_max_months')
                            ->label('Age range (max months)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(11)
                            ->helperText('Optional. Additional months for max age (0-11).')
                            ->placeholder('e.g. 3'),
                        Textarea::make('description'),
                        Section::make()
                            ->schema([
                                ToggleButtons::make('is_maternal')
                                    ->live()
                                    ->default(false)
                                    ->label('Is Maternal Category?')
                                    ->grouped()
                                    ->boolean(),
                                ToggleButtons::make('is_active')
                                    ->label('Is Active?')
                                    ->default(true)
                                    ->grouped()
                                    ->boolean(),
                            ])
                            ->columns(2),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('age_range_display')
                    ->label('Age range')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state ?? '—'),
                TextColumn::make('description')->searchable()->limit(40),
            ])
            ->filters([
                //
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    // initialize auth user
    public static function currentUser(): ?User
    {
        return Auth::user();
    }
}