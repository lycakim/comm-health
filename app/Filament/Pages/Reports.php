<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\Report;
use App\Enums\RoleEnum;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Components\Tab;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Concerns\InteractsWithTable;

class Reports extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';

    protected static string $view = 'filament.pages.reports';

    protected static ?string $navigationGroup = 'Data  & Reports';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return in_array(self::currentUser()->role, [
            RoleEnum::ADMIN,
            RoleEnum::MHO,
            RoleEnum::BHW,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_report')
                ->label('Generate Report')
                ->icon('heroicon-o-plus')
                ->disabled(),
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'View and download reports';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Generated Reports')
            ->query(fn () => Report::latest())
            ->columns([
                TextColumn::make('title')->searchable()
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ]);
    }

    // initialize auth user
    public static function currentUser(): ?User
    {
        return Auth::user();
    }
}