<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;

class ActivityLogsWidget extends BaseWidget
{
    protected static ?string $heading = 'Activity Logs';

    protected static ?string $description = 'Recent system activities and user actions';

    protected static ?int $sort = 5;

    public function getColumnSpan(): int | string | array
    {
        $user = Auth::user();
        // Make columnSpanFull && !$user->isMidwife())  when user is NOT MHO
        if ($user && !$user->isBHW()){;
            return 'full';
        }
        
        return parent::getColumnSpan();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ActivityLog::query()->latest()->limit(10))
            ->columns([
                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('createdBy')
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return 'System';
                        }
                        $role = strtoupper($state->role->value ?? 'N/A');
                        return $state->name . ' (' . $role . ')';
                    })
                    ->label('User')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->formatStateUsing(fn($state) => $state->format('M d, Y g:i A'))
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
    }

    protected function getType(): string
    {
        return 'table';
    }
}