<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Actions\Action;
use App\Filament\Resources\PatientResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Set;
use App\Enums\SexEnum;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isMHO() || auth()->user()->isBHW() || auth()->user()->isMidwife();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('first_name')->required(),
                                TextInput::make('last_name')->required(),
                                // Select::make('resident_id')
                                //     ->label('Resident')
                                //     ->options(User::query()->get()->pluck('name', 'id')->toArray())
                                //     ->columnSpanFull(),
                                TextInput::make('middle_name')
                                    ->required()
                                    ->hidden(fn (Get $get): bool => !$get('add_middle_name'))
                                    ->columnSpanFull(),
                                DatePicker::make('birth_date')
                                    ->label('Date of Birth')
                                    ->required(),
                                Select::make('sex')
                                    ->label('Gender')
                                    ->options([
                                        'male' => 'Male',
                                        'female' => 'Female',
                                    ])
                                    ->required(),
                                Select::make('category_id')
                                    ->label('Category')
                                    ->options(Category::query()->get()->pluck('name', 'id')->toArray())
                                    ->columnSpanFull()
                                    ->preload()
                                    ->searchable(),
                                Select::make('barangay_id')
                                    ->label('Barangay')
                                    ->options(Barangay::query()->get()->pluck('name', 'id')->toArray())
                                    ->preload()
                                    ->searchable(),
                                DatePicker::make('last_visit'),
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
                    ->searchable(['first_name', 'last_name'])
                    ->getStateUsing(function ($record) {
                        return $record->first_name . ' ' . $record->last_name;
                    }),
                TextColumn::make('sex')
                    ->formatStateUsing(fn ($state) => SexEnum::tryFrom($state)?->getLabel() ?? ucfirst($state))
                    ->badge()
                    ->color(fn (string $state): string => SexEnum::tryFrom($state)?->getColor() ?? 'gray'),
                TextColumn::make('age')
                    ->getStateUsing(function ($record) {
                        return Carbon::parse($record->birth_date)->age;
                    })
                    ->label('Age'),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable(['category.id', 'category.name']),
                TextColumn::make('barangay.name')
                    ->label('Barangay')
                    ->searchable(['barangay.id', 'barangay.name']),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('barangay_id')
                    ->label('Barangay')
                    ->relationship('barangay', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('sex')
                    ->label('Sex')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                    ])    
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('add_new_user')
                    ->label('Create Access')
                    ->hidden(fn ($record) => $record->user_id)
                    ->accessSelectedRecords()
                    ->color('warning')
                    ->icon('heroicon-o-plus')
                    ->form([
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->unique('users', 'email')
                            ->suffixAction(
                                Action::make('fill_from_name')
                                    ->label('Suggest from name')
                                    ->icon('heroicon-o-sparkles')
                                    ->action(function (Set $set, $record) {
                                        if ($record && $record->first_name && $record->last_name) {
                                            $firstName = strtolower($record->first_name);
                                            $lastName = strtolower($record->last_name);
                                            $suggestedEmail = "{$firstName}.{$lastName}@gmail.com";
                                            $set('email', $suggestedEmail);
                                        }
                                    })
                            ),
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->revealable()
                            ->helperText('Please take note of your password. You will need it to access your medical records.')
                            ->confirmed(),
                        TextInput::make('password_confirmation')
                            ->label('Confirm Password')
                            ->password()
                            ->revealable()
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Create Patient Access')
                    ->modalDescription(fn ($record) => new \Illuminate\Support\HtmlString("You are about to create login credentials for this <strong>{$record->first_name} {$record->last_name}</strong>. They will be able to access their medical records online."))
                    ->modalSubmitActionLabel('Yes, create access')
                    ->modalIcon('heroicon-o-user-plus')
                    ->action(function (array $data, $record) {
                        $user = User::create([
                            'name' => $record->first_name . ' ' . $record->last_name,
                            'email' => $data['email'],
                            'password' => Hash::make($data['password']),
                        ]);

                        $record->user_id = $user->id;
                        $record->save();
                    
                        Notification::make()
                            ->title('User created successfully')
                            ->success()
                            ->send();
                    }),
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