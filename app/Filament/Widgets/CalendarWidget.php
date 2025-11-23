<?php

namespace App\Filament\Widgets;

use App\Models\Program;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\ProgramResource;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;

class CalendarWidget extends FullCalendarWidget implements HasActions
{
    use InteractsWithActions;

    public Model | string | null $model = Program::class;
    
    protected int | string | array $columnSpan = 'full';

    public ?Program $selectedEvent = null;

    public function fetchEvents(array $fetchInfo): array
    {
        return Program::query()
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