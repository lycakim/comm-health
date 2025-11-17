<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Enums\RoleEnum;
use App\Models\Program;
use App\Models\Barangay;
use App\Models\Category;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Traits\HasUserTypeUrls;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Resources\Components\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\ProgramResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProgramResource\RelationManagers;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfolistSection;

class ProgramResource extends Resource
{
    use HasUserTypeUrls;
    
    protected static ?string $model = Program::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        $user = self::currentUser();
        if ($user->isBHW() || $user->isMidwife()) {
            return 'Programs';
        }
        return 'Utility';
    }

    public static function getNavigationSort(): ?int
    {
        $user = self::currentUser();
        if ($user->isMHO()) {
            return 2;
        }
        else if ($user->isBHW() || $user->isMidwife()) {
            return 1;
        }
        
        return 4;
    }

    public static function getPluralModelLabel(): string
    {
        if (self::currentUser()->isMHO()) {
            return 'Health Programs';
        }
        return 'Programs';
    }

    // Full access to the resource (all roles that can see it)
    public static function canAccess(): bool
    {
        return in_array(self::currentUser()->role, [
            RoleEnum::ADMIN,
            RoleEnum::MHO,
            RoleEnum::BHW,      // Can access but read-only
            RoleEnum::MIDWIFE   // Can access and edit
        ]);
    }

    // Can view individual records
    public static function canView(Model $record): bool
    {
        return in_array(self::currentUser()->role, [
            RoleEnum::ADMIN,
            RoleEnum::MHO,
            RoleEnum::BHW,
            RoleEnum::MIDWIFE
        ]);
    }

    // Can edit (excludes BHW)
    public static function canEdit(Model $record): bool
    {
        return in_array(self::currentUser()->role, [
            RoleEnum::ADMIN,
            RoleEnum::MHO,
            RoleEnum::MIDWIFE
        ]);
    }

    // Can delete (excludes BHW)
    public static function canDelete(Model $record): bool
    {
        return in_array(self::currentUser()->role, [
            RoleEnum::ADMIN,
            RoleEnum::MHO,
            RoleEnum::MIDWIFE
        ]);
    }

    // Can create (excludes BHW)
    public static function canCreate(): bool
    {
        return in_array(self::currentUser()->role, [
            RoleEnum::ADMIN,
            RoleEnum::MHO,
            RoleEnum::MIDWIFE
        ]);
    }

    public static function form(Form $form): Form
    {
        $isReadOnly = self::currentUser()->role === RoleEnum::BHW;
        
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->label('Program Name')
                            ->required()
                            ->disabled($isReadOnly),
                        Select::make('category_id')
                            ->label('Category')
                            ->searchable()
                            ->options(Category::query()->get()->pluck('name', 'id')->toArray())
                            ->required()
                            ->disabled($isReadOnly),
                        Select::make('barangay_id')
                            ->label('Barangay')
                            ->searchable()
                            ->options(Barangay::query()->get()->pluck('name', 'id')->toArray())
                            ->required()
                            ->disabled($isReadOnly),
                        Textarea::make('description')
                            ->columnSpanFull()
                            ->disabled($isReadOnly),
                        DatePicker::make('program_date')
                            ->required()
                            ->disabled($isReadOnly),
                        TimePicker::make('program_start_time')
                            ->required()
                            ->disabled($isReadOnly),
                        TimePicker::make('program_end_time')
                            ->required()
                            ->disabled($isReadOnly),
                        
                        Select::make('coordinator')
                            ->label('Coordinator')
                            ->options(User::query()->get()->pluck('name', 'id')->toArray())
                            ->disabled($isReadOnly),
                    ])
                    ->columns(3),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Program Information')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Program Name'),
                        TextEntry::make('category.name')
                            ->label('Category'),
                        TextEntry::make('barangay.name')
                            ->label('Barangay'),
                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                        TextEntry::make('program_date')
                            ->label('Program Date')
                            ->date(),
                        TextEntry::make('program_start_time')
                            ->label('Start Time')
                            ->time(),
                        TextEntry::make('program_end_time')
                            ->label('End Time')
                            ->time(),
                        TextEntry::make('coordinatorUser.name')
                            ->label('Coordinator'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        $isBHWOrMidwife = in_array(self::currentUser()->role, [RoleEnum::BHW, RoleEnum::MIDWIFE]);
        
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('barangay.name')
                    ->label('Assigned Barangay')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Show View action for BHW and Midwife
                Tables\Actions\ViewAction::make()
                    ->visible(fn () => $isBHWOrMidwife),
                // Show Edit action for those who can edit
                Tables\Actions\EditAction::make()
                    ->visible(fn () => !$isBHWOrMidwife),
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
            'index' => Pages\ListPrograms::route('/'),
            'create' => Pages\CreateProgram::route('/create'),
            'view' => Pages\ViewProgram::route('/{record}'),
            'edit' => Pages\EditProgram::route('/{record}/edit'),
        ];
    }

    // initialize auth user
    public static function currentUser(): ?User
    {
        return Auth::user();
    }
}