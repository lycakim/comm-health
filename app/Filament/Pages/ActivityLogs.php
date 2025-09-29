<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;

class ActivityLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string $view = 'filament.pages.activity-logs';

    protected static ?string $navigationGroup = 'Data  & Reports';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return Auth::user()->isAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ActivityLog::query()->latest())
            ->columns([
                TextColumn::make('description')->label('Description')->searchable(),
                TextColumn::make('createdBy')
                    ->formatStateUsing(function ($state) {
                        $role = strtoupper($state->role->value); // Get the enum's value
                        return $state->name . ' (' . $role . ')';
                    })
                    ->label('Causer By')->searchable(),
                TextColumn::make('created_at')->formatStateUsing(fn($state) => $state->format('M d, Y g:i a'))->searchable(),
            ])
            ->filters([
                // ...
            ])
            ->actions([
                // ...
            ])
            ->bulkActions([
                // ...
            ]);
    }
}