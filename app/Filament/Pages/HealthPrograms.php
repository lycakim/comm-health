<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use App\Models\User;
use App\Enums\RoleEnum;
use App\Models\Program;
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

class HealthPrograms extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static string $view = 'filament.pages.health-programs';

    protected static ?string $navigationGroup = 'Programs';

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
        $isBHWOrMidwife = in_array(self::currentUser()->role, [RoleEnum::BHW, RoleEnum::MIDWIFE]);
        
        return $table
            ->query(
                Program::latest()
                    ->when(
                        Auth::user()->role !== RoleEnum::MHO->value,
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
                Action::make('export_csv')
                    ->label('Export CSV')
                    ->disabled(fn() => is_null(Auth::user()->barangay_id) || Auth::user()->role === RoleEnum::MHO->value)
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->form([
                        Select::make('format')
                            ->label('Export Format')
                            ->options([
                                'csv' => 'CSV (Spreadsheet)',
                                'pdf' => 'PDF (Document)',
                            ])
                            ->default('csv')
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
                    ->visible(fn () => $isBHWOrMidwife),
                // button for sending sms to all patients in the barangay
                Action::make('send_sms')
                    ->label('Send SMS')
                    ->modalHeading('Send SMS Notification')
                    ->modalSubmitActionLabel('Confirm & Send SMS')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn ($record) => Auth::user()->isBHW() && $record->program_end_date > now())

                    ->form(function ($record) {
                        $users = \App\Models\Patient::where('barangay_id', $record->barangay_id)->where('category_id', $record->category_id)->get();

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
                                        ->label('Datexxx')
                                        ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('F d, Y') : null)
                                        ->default($record->program_start_date)
                                        ->disabled(),
                                ]),

                            \Filament\Forms\Components\Section::make('Recipients (' . $users->count() . ')')
                                ->description('List of patients under the selected barangay.')
                                ->schema([
                                    \Filament\Forms\Components\View::make('filament.custom.user-list')
                                        ->viewData(['users' => $users]),
                                ]),
                        ];
                    })

                    ->action(function ($record) {
                        $program = $record;
                        $users = \App\Models\Patient::where('barangay_id', $program->barangay_id)->where('category_id', $record->category_id)->get();

                        $smsService = app(\App\Services\SemaphoreService::class);

                        $successCount = 0;
                        $failCount = 0;
                        $invalidNumbers = []; // Will store patients with invalid/missing numbers
                        $failedNumbers = []; // Will store patients with failed SMS sends

                        // Format program date and time
                        $programStartDate = $program->program_start_date ? Carbon::parse($program->program_start_date)->format('F d, Y') : 'TBA';
                        $programEndDate = $program->program_start_date ? Carbon::parse($program->program_start_date)->format('F d, Y') : 'TBA';
                        $startTime = $program->program_start_time ? Carbon::parse($program->program_start_time)->format('g:i A') : 'TBA';
                        $endTime = $program->program_end_time ? Carbon::parse($program->program_end_time)->format('g:i A') : 'TBA';

                        if ($programStartDate === $programEndDate) {
                            $programDate = $programStartDate;
                        } else {
                            $programDate = "{$programStartDate} - {$programEndDate}";
                        }

                        foreach ($users as $user) {
                            // Skip if contact number is empty
                            if (empty($user->contact_number)) {
                                $invalidNumbers[] = $user->first_name . ' ' . $user->last_name;
                                $failCount++;
                                continue;
                            }

                            // Create personalized message with program details
                            $message = "Maayong adlaw {$user->first_name}!\n\n";
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

                            $result = $smsService->sendSMS($user->contact_number, $message);

                            if ($result['success']) {
                                $successCount++;
                            } else {
                                $failCount++;
                                $failedNumbers[] = $user->first_name . ' ' . $user->last_name . ' (' . ($result['message'] ?? 'Unknown error') . ')';
                                \Illuminate\Support\Facades\Log::warning('SMS send failed for patient', [
                                    'patient_id' => $user->id,
                                    'patient_name' => $user->first_name . ' ' . $user->last_name,
                                    'contact_number' => $user->contact_number,
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