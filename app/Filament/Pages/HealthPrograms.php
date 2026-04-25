<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use App\Models\User;
use App\Enums\RoleEnum;
use App\Models\Program;
use App\Exports\HealthProgramsExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Concerns\HasDashboardBreadcrumb;

class HealthPrograms extends Page implements HasTable
{
    use HasDashboardBreadcrumb;
    use InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static string $view = 'filament.pages.health-programs';

    protected static ?string $navigationGroup = 'Programs';

    protected static ?int $navigationSort = 1;

    public string $activeTab = 'calendar';

    public static function canAccess(): bool
    {
        return in_array(self::currentUser()->role, [
            RoleEnum::BHW,
            RoleEnum::MIDWIFE,
            RoleEnum::ADMIN,
        ]);
    }

    public static function currentUser(): ?User
    {
        return Auth::user();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Program::latest()
                    ->when(
                        in_array(Auth::user()->role, [RoleEnum::BHW, RoleEnum::MIDWIFE]),
                        function ($query) {
                            $barangayId = Auth::user()->barangay_id;
                            if ($barangayId) {
                                $query->where('barangay_id', $barangayId);
                            } else {
                                $query->whereRaw('1 = 0');
                            }
                        }
                    )
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('barangay.name')
                    ->label('Assigned Barangay')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable(),
            ])
            ->headerActions([
                Action::make('create_program')
                    ->label('Create Barangay Program')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->url(\App\Filament\Resources\ProgramResource::getUrl('create'))
                    ->visible(fn () => in_array(Auth::user()->role, [RoleEnum::BHW, RoleEnum::MIDWIFE]) && (Auth::user()->barangay_id || Auth::user()->barangays()->exists())),
                Action::make('export_csv')
                    ->label('Export CSV')
                    ->disabled(fn() => is_null(Auth::user()->barangay_id) || Auth::user()->role === RoleEnum::MHO->value)
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->form([
                        Select::make('format')
                            ->label('Export Format')
                            ->options([
                                'xlsx' => 'Excel (XLSX)',
                                'pdf' => 'PDF (Document)',
                            ])
                            ->default('xlsx')
                            ->required()
                    ])
                    ->action(function ($data) {
                        $programs = Program::with(['barangay', 'category'])->get();
                        $title = 'Health Programs Report';
                        $barangay = Auth::user()->barangay_id ? \App\Models\Barangay::find(Auth::user()->barangay_id) : null;

                        // Handle PDF export
                        if ($data['format'] === 'pdf') {
                            $pdfService = app(\App\Services\PDFGenerationService::class);
                            $pdf = $pdfService->generateProgramListPdf($programs, $title, $barangay);
                            $filename = 'health-programs_' . date('Y-m-d_His') . '.pdf';
                            
                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                $filename,
                                ['Content-Type' => 'application/pdf']
                            );
                        }

                        // Handle XLSX export
                        if ($data['format'] === 'xlsx') {
                            $user = Auth::user();
                            $user->load('barangay');
                            return Excel::download(
                                new HealthProgramsExport($programs, $title, $barangay, $user->getPreparedByLabelForExport()),
                                'health-programs_' . date('Y-m-d_His') . '.xlsx',
                                \Maatwebsite\Excel\Excel::XLSX
                            );
                        }

                        // Handle CSV export
                        return response()->streamDownload(function () use ($programs) {
                            $csv = fopen('php://output', 'w');
                            fputcsv($csv, ['Program Name', 'Barangay', 'Category', 'Date']);
                            foreach ($programs as $program) {
                                fputcsv($csv, [
                                    $program->name, 
                                    $program->barangay->name ?? 'N/A', 
                                    $program->category->name ?? 'N/A', 
                                    $program->program_start_date ? Carbon::parse($program->program_start_date)->format('Y-m-d') : 'N/A'
                                ]);
                            }
                            fclose($csv);
                        }, 'health-programs_' . date('Y-m-d_His') . '.csv');
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make()
                    ->visible(fn () => Auth::user()->isMHO() || Auth::user()->isAdmin())
                    ->modalHeading(fn ($record) => $record->name ?? 'Program Details')
                    ->infolist(fn (Infolist $infolist) => $infolist
                        ->schema([
                            Section::make('Program Information')
                                ->schema([
                                    TextEntry::make('name')
                                        ->label('Program Name')
                                        ->weight('bold')
                                        ->size('lg')
                                        ->columnSpanFull(),
                                    TextEntry::make('category.name')
                                        ->label('Category')
                                        ->badge()
                                        ->color('primary')
                                        ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : 'N/A'),
                                    TextEntry::make('barangay.name')
                                        ->label('Barangay')
                                        ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : 'N/A'),
                                    TextEntry::make('coordinatorUser.name')
                                        ->label('Coordinator')
                                        ->formatStateUsing(fn ($state) => $state ? $state : 'N/A')
                                        ->placeholder('N/A'),
                                    TextEntry::make('program_start_date')
                                        ->label('Start Date')
                                        ->date('F d, Y')
                                        ->placeholder('N/A'),
                                    TextEntry::make('program_end_date')
                                        ->label('End Date')
                                        ->date('F d, Y')
                                        ->placeholder('N/A'),
                                    TextEntry::make('program_start_time')
                                        ->label('Start Time')
                                        ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('g:i A') : 'N/A')
                                        ->placeholder('N/A'),
                                    TextEntry::make('program_end_time')
                                        ->label('End Time')
                                        ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('g:i A') : 'N/A')
                                        ->placeholder('N/A'),
                                    TextEntry::make('description')
                                        ->label('Description')
                                        ->columnSpanFull()
                                        ->placeholder('No description available.')
                                        ->html()
                                        ->prose(),
                                ])
                                ->columns(3),
                        ])
                    ),
                // button for sending sms to all patients in the barangay
                Action::make('send_sms')
                    ->label('Send SMS')
                    ->modalHeading('Send SMS Notification')
                    ->modalSubmitActionLabel('Confirm & Send SMS')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn ($record) => (Auth::user()->isMHO() || Auth::user()->isAdmin() || Auth::user()->isBHW() || Auth::user()->isMidwife()) && $record->program_end_date > now())

                    ->form(function ($record) {
                        $users = $record->getSmsRecipientsQuery()->get();

                        return [
                            \Filament\Forms\Components\Section::make('Program Details')
                                ->schema([
                                    \Filament\Forms\Components\TextInput::make('name')
                                        ->label('Program Name')
                                        ->default($record->name)
                                        ->disabled(),

                                    \Filament\Forms\Components\Textarea::make('description')
                                        ->label('Description')
                                        ->default($record->description)
                                        ->disabled(),

                                    \Filament\Forms\Components\TextInput::make('program_start_date')
                                        ->label('Date')
                                        ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('F d, Y') : null)
                                        ->default($record->program_start_date)
                                        ->disabled(),
                                ]),

                            \Filament\Forms\Components\Section::make('Recipients (' . $users->count() . ')')
                                ->description(\App\Models\Category::find($record->category_id)?->isProfiledRegisteredMembers()
                                    ? 'All residents (Profiled/Registered Members). SMS will be sent to household heads only.'
                                    : 'List of patients under the selected barangay and category. SMS will be sent to household heads only.')
                                ->schema([
                                    \Filament\Forms\Components\View::make('filament.custom.user-list')
                                        ->viewData(['users' => $users]),
                                ]),
                        ];
                    })

                    ->action(function ($record) {
                        $program = $record;
                        $residents = $program->getSmsRecipientsQuery()->get();
                        $recipients = \App\Models\Patient::uniqueHouseholdHeadsForSms($residents);

                        $smsService = app(\App\Services\SemaphoreService::class);

                        $successCount = 0;
                        $failCount = 0;
                        $invalidNumbers = [];
                        $failedNumbers = [];

                        $programStartDate = $program->program_start_date ? Carbon::parse($program->program_start_date)->format('F d, Y') : 'TBA';
                        $programEndDate = $program->program_end_date ? Carbon::parse($program->program_end_date)->format('F d, Y') : 'TBA';
                        $startTime = $program->program_start_time ? Carbon::parse($program->program_start_time)->format('g:i A') : 'TBA';
                        $endTime = $program->program_end_time ? Carbon::parse($program->program_end_time)->format('g:i A') : 'TBA';

                        if ($programStartDate === $programEndDate) {
                            $programDate = $programStartDate;
                        } else {
                            $programDate = "{$programStartDate} - {$programEndDate}";
                        }

                        foreach ($recipients as $head) {
                            if (empty($head->contact_number)) {
                                $invalidNumbers[] = $head->first_name . ' ' . $head->last_name;
                                $failCount++;
                                continue;
                            }

                            $message = "Maayong adlaw {$head->first_name}!\n\n";
                            $message .= "Nagpahibalo ang Barangay nga aduna kita'y {$program->name}\n";
                            $message .= "Petsa: {$programDate}\n";
                            $message .= "Oras: {$startTime} - {$endTime}\n";

                            if (!empty($program->description)) {
                                $description = strlen($program->description) > 100 
                                    ? substr($program->description, 0, 100) . '...' 
                                    : $program->description;
                                $message .= "\n{$description}\n";
                            }

                            $message .= "\nPalihug mangadto sa takdang oras aron matagaan og hustong serbisyo.";
                            $message .= "\nDaghang salamat ug kita-kits!";

                            $result = $smsService->sendSMSWithRateLimit($head->contact_number, $message);

                            if ($result['success']) {
                                $successCount++;
                            } else {
                                $failCount++;
                                $failedNumbers[] = $head->first_name . ' ' . $head->last_name . ' (' . ($result['message'] ?? 'Unknown error') . ')';
                                \Illuminate\Support\Facades\Log::warning('SMS send failed for patient', [
                                    'patient_id' => $head->id,
                                    'patient_name' => $head->first_name . ' ' . $head->last_name,
                                    'contact_number' => $head->contact_number,
                                    'formatted_number' => $result['formatted_number'] ?? null,
                                    'error' => $result['message'] ?? 'Unknown error',
                                    'api_response' => $result['data'] ?? null
                                ]);
                            }
                        }

                        // Build notification message
                        $notificationBody = '';
                        if ($failCount === 0) {
                            $notificationBody = "Successfully sent {$successCount} SMS notification(s).";
                        } elseif ($successCount > 0) {
                            $notificationBody = "Sent {$successCount} SMS successfully, but {$failCount} failed.";
                            if (!empty($invalidNumbers)) {
                                $notificationBody .= " Invalid/missing numbers: " . implode(', ', array_slice($invalidNumbers, 0, 5));
                                if (count($invalidNumbers) > 5) {
                                    $notificationBody .= " and " . (count($invalidNumbers) - 5) . " more.";
                                }
                            }
                            if (!empty($failedNumbers)) {
                                $failedList = implode(', ', array_slice($failedNumbers, 0, 3));
                                if (count($failedNumbers) > 3) {
                                    $failedList .= " and " . (count($failedNumbers) - 3) . " more";
                                }
                                $notificationBody .= " Failed sends: " . $failedList . ". Check logs for details.";
                            }
                        } else {
                            $notificationBody = "Failed to send all {$failCount} SMS notifications.";
                            if (!empty($invalidNumbers)) {
                                $notificationBody .= " Invalid/missing numbers: " . implode(', ', array_slice($invalidNumbers, 0, 5));
                            }
                            if (!empty($failedNumbers)) {
                                $failedList = implode(', ', array_slice($failedNumbers, 0, 3));
                                if (count($failedNumbers) > 3) {
                                    $failedList .= " and " . (count($failedNumbers) - 3) . " more";
                                }
                                $notificationBody .= " Errors: " . $failedList . ". Check logs for details.";
                            }
                        }

                        if ($failCount === 0) {
                            Notification::make()
                                ->title('SMS Sent Successfully')
                                ->success()
                                ->body($notificationBody)
                                ->send();
                        } elseif ($successCount > 0) {
                            Notification::make()
                                ->title('SMS Partially Sent')
                                ->warning()
                                ->body($notificationBody)
                                ->send();
                        } else {
                            Notification::make()
                                ->title('SMS Failed')
                                ->danger()
                                ->body($notificationBody)
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
               //
            ]);
    }
}