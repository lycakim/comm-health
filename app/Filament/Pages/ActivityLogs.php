<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string $view = 'filament.pages.activity-logs';

    protected static ?string $navigationGroup = 'Data  & Reports';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return Auth::user()->isAdmin() || Auth::user()->isMHO();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ActivityLog::query()->with('createdBy')->latest())
            ->columns([
                TextColumn::make('description')->label('Description')->searchable(),
                TextColumn::make('createdBy')
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return 'System';
                        }
                        $role = strtoupper($state->role->value ?? 'N/A'); // Get the enum's value
                        return $state->name . ' (' . $role . ')';
                    })
                    ->label('Causer By')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('createdBy', function (Builder $q) use ($search) {
                            $searchLower = strtolower($search);
                            $q->where('name', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%")
                              ->orWhere('role', 'like', "%{$searchLower}%");
                        });
                    }),
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