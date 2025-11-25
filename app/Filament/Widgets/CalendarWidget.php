<?php

namespace App\Filament\Widgets;

use App\Models\Program;
use Filament\Actions\Action;
use Filament\Widgets\Widget;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\Contracts\HasActions;
use Filament\Infolists\Components\Section;
use App\Filament\Resources\ProgramResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Concerns\InteractsWithActions;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget implements HasActions
{
    use InteractsWithActions;

    public Model | string | null $model = Program::class;
    
    protected int | string | array $columnSpan = 'full';

    public ?Program $selectedEvent = null;

    public function fetchEvents(array $fetchInfo): array
    {
        return Program::query()
            ->when(
                !Auth::user()->role === 'mho',
                function ($query) {
                    $user = Auth::user();
                    $barangay = $user->barangays->first();
                    if ($barangay) {
                        $query->where('barangay_id', $barangay->id);
                    } else {
                        // Optionally, return no records if no assigned barangay
                        $query->whereRaw('1 = 0');
                    }
                }
            )
            ->where(function ($query) use ($fetchInfo) {
                $query->whereBetween('program_start_date', [$fetchInfo['start'], $fetchInfo['end']])
                    ->orWhereBetween('program_end_date', [$fetchInfo['start'], $fetchInfo['end']])
                    ->orWhere(function ($q) use ($fetchInfo) {
                        $q->where('program_start_date', '<=', $fetchInfo['start'])
                            ->where('program_end_date', '>=', $fetchInfo['end']);
                    });
            })
            ->get()
            ->map(fn (Program $event) => [
                'id' => $event->id,
                'title' => $event->name,
                'start' => $event->program_start_date,
                'end'   => $event->program_end_date,
            ])
            ->toArray();
    }

    public function onEventClick($event): void
    {
        $program = Program::with(['category', 'barangay'])->find($event['id']);

        if (!$program) {
            return;
        }
        
        $this->selectedEvent = $program;
        
        $this->mountAction('viewProgram');
    }

    public function viewProgramAction(): Action
    {
        return Action::make('viewProgram')
            ->modalHeading(fn () => $this->selectedEvent?->name ?? 'Program Details')
            ->infolist(fn (Infolist $infolist) => $infolist
                ->record($this->selectedEvent)
                ->schema([
                    Section::make()
                        ->schema([
                            TextEntry::make('program_start_date')
                                ->label('Start Date')
                                ->date('M d, Y')
                                ->placeholder('N/A'),
                            TextEntry::make('program_end_date')
                                ->label('End Date')
                                ->date('M d, Y')
                                ->placeholder('N/A'),
                            TextEntry::make('program_start_time')
                                ->label('Start Time')
                                ->dateTime('H:i A'),
                            TextEntry::make('program_end_time')
                                ->label('End Time')
                                ->dateTime('H:i A'),
                            TextEntry::make('category.name')
                                ->label('Category')
                                ->badge()
                                ->color(fn () => 'primary')
                                ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : 'N/A'),
                            TextEntry::make('barangay.name')
                                ->label('Barangay')
                                ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : 'N/A'),
                            TextEntry::make('description')
                                ->label('Description')
                                ->columnSpanFull()
                                ->placeholder('No description available.')
                                ->html()
                                ->prose(),
                        ])
                        ->columns(2),
                ])
            )
            ->modalWidth('2xl')
            ->modalFooterActions(fn () => [])
            ->closeModalByClickingAway(true);
    }

    public function headerActions(): array
    {
        return [];
    }

    public function getViewData(): array
    {
        return [
            'config' => [
                'initialView' => 'dayGridMonth',
                'headerToolbar' => [
                    'left' => 'prev,next today',
                    'center' => 'title',
                    'right' => 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
                ],
                'editable' => false,
                'selectable' => false,
                'selectMirror' => false,
                'dayMaxEvents' => true,
                'navLinks' => true,
                'firstDay' => 1,
                'locale' => 'en',
                'timeZone' => 'UTC',
                'height' => 'auto',
                'eventDisplay' => 'auto',
            ],
        ];
    }
}