<?php

namespace App\Filament\Widgets;

use App\Enums\RoleEnum;
use App\Models\Program;
use App\Models\Consultation;
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
    public ?Consultation $selectedConsultation = null;
    public ?string $eventType = null;

    public function fetchEvents(array $fetchInfo): array
    {
        $user = Auth::user();
        
        // Fetch Programs - Admin: all; MHO: exclude BHW-created; BHW/Midwife: filter by barangay
        $programs = Program::query()
            ->with(['category', 'barangay', 'coordinatorUser', 'createdByUser'])
            ->when(
                $user->role === RoleEnum::MHO,
                fn ($query) => $query->where(function ($q) {
                    $q->whereHas('createdByUser', fn ($sub) => $sub->whereIn('role', [RoleEnum::ADMIN, RoleEnum::MHO]))
                        ->orWhere(function ($sub) {
                            $sub->whereNull('created_by')
                                ->whereHas('coordinatorUser', fn ($c) => $c->whereIn('role', [RoleEnum::ADMIN, RoleEnum::MHO]));
                        })
                        ->orWhereNull('created_by');
                })
            )
            ->when(
                in_array($user->role, [RoleEnum::BHW, RoleEnum::MIDWIFE]),
                function ($query) use ($user) {
                    $barangayId = $user->barangay_id ?? $user->barangays()->first()?->id;
                    if ($barangayId) {
                        $query->where('barangay_id', $barangayId);
                    } else {
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
            ->map(function (Program $event) {
                $startDate = $event->program_start_date ? $event->program_start_date->format('M d, Y') : 'N/A';
                $endDate = $event->program_end_date ? $event->program_end_date->format('M d, Y') : 'N/A';
                $startTime = $event->program_start_time ? \Carbon\Carbon::parse($event->program_start_time)->format('g:i A') : 'N/A';
                $endTime = $event->program_end_time ? \Carbon\Carbon::parse($event->program_end_time)->format('g:i A') : 'N/A';
                
                return [
                    'id' => $event->id,
                    'title' => $event->name,
                    'start' => $event->program_start_date,
                    'end' => $event->program_end_date,
                    'color' => '#10b981', // Green for programs
                    'extendedProps' => [
                        'type' => 'program',
                        'name' => $event->name,
                        'description' => $event->description ?? 'No description available.',
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'startTime' => $startTime,
                        'endTime' => $endTime,
                        'barangay' => $event->barangay->name ?? 'N/A',
                        'category' => $event->category->name ?? 'N/A',
                        'coordinator' => $event->coordinatorUser->name ?? 'N/A',
                    ]
                ];
            });

        // Fetch Consultations - BHW/Midwife: filter by resident's barangay; no barangay_id = no events
        $consultations = Consultation::query()
            ->when(
                in_array($user->role, [RoleEnum::BHW, RoleEnum::MIDWIFE]),
                function ($query) use ($user) {
                    if ($user->barangay_id) {
                        $query->whereHas('patient', function ($q) use ($user) {
                            $q->where('barangay_id', $user->barangay_id);
                        });
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }
            )
            ->whereBetween('date', [$fetchInfo['start'], $fetchInfo['end']])
            ->with('patient')
            ->get()
            ->map(fn (Consultation $consultation) => [
                'id' => $consultation->id,
                'title' => 'Consultation: ' . ($consultation->patient->first_name ?? 'Unknown'),
                'start' => $consultation->date,
                'end' => $consultation->date,
                'color' => '#3b82f6', // Blue for consultations
                'extendedProps' => [
                    'type' => 'consultation'
                ]
            ]);

        return $programs->concat($consultations)->toArray();
    }

    public function onEventClick($event): void
    {
        $eventType = $event['extendedProps']['type'] ?? 'program';
        $this->eventType = $eventType;

        if ($eventType === 'program') {
            $program = Program::with(['category', 'barangay', 'coordinatorUser'])->find($event['id']);
            
            if (!$program) {
                return;
            }
            
            $this->selectedEvent = $program;
            $this->mountAction('viewProgram');
        } else {
            $consultation = Consultation::with(['patient', 'category'])->find($event['id']);
            
            if (!$consultation) {
                return;
            }
            
            $this->selectedConsultation = $consultation;
            $this->mountAction('viewConsultation');
        }
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
                            TextEntry::make('name')
                                ->label('Program Name')
                                ->weight('bold')
                                ->size('lg'),
                            TextEntry::make('date_range')
                                ->label('Date Range')
                                ->formatStateUsing(function ($record) {
                                    $start = $record->program_start_date ? $record->program_start_date->format('M d, Y') : 'N/A';
                                    $end = $record->program_end_date ? $record->program_end_date->format('M d, Y') : 'N/A';
                                    return "{$start} to {$end}";
                                }),
                            TextEntry::make('program_start_time')
                                ->label('Start Time')
                                ->dateTime('H:i A')
                                ->placeholder('N/A'),
                            TextEntry::make('program_end_time')
                                ->label('End Time')
                                ->dateTime('H:i A')
                                ->placeholder('N/A'),
                            TextEntry::make('barangay.name')
                                ->label('Barangay')
                                ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : 'N/A'),
                            TextEntry::make('coordinatorUser.name')
                                ->label('Coordinator')
                                ->formatStateUsing(fn ($state) => $state ? $state : 'N/A')
                                ->placeholder('N/A'),
                            TextEntry::make('category.name')
                                ->label('Category')
                                ->badge()
                                ->color(fn () => 'primary')
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

    public function viewConsultationAction(): Action
    {
        return Action::make('viewConsultation')
            ->modalHeading(fn () => 'Resident Consultation Details')
            ->infolist(fn (Infolist $infolist) => $infolist
                ->record($this->selectedConsultation)
                ->schema([
                    Section::make()
                        ->schema([
                            TextEntry::make('patient.full_name')
                                ->label('Resident Name')
                                ->formatStateUsing(fn ($record) => 
                                    ($record->patient->first_name ?? '') . ' ' . 
                                    ($record->patient->last_name ?? '')
                                ),
                            TextEntry::make('date')
                                ->label('Resident Consultation Date')
                                ->date('M d, Y'),
                            TextEntry::make('category.name')
                                ->label('Category')
                                ->badge()
                                ->color('success')
                                ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : 'N/A'),
                            TextEntry::make('chief_complaint')
                                ->label('Chief Complaint')
                                ->columnSpanFull()
                                ->placeholder('N/A'),
                            TextEntry::make('diagnosis')
                                ->label('Diagnosis')
                                ->columnSpanFull()
                                ->placeholder('N/A'),
                            TextEntry::make('treatment')
                                ->label('Treatment')
                                ->columnSpanFull()
                                ->placeholder('N/A'),
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