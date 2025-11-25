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
                            $barangayId = Auth::user()->barangays()->first()->id;
                            
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
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function () {
                        $programs = Program::all();
                        return response()->streamDownload(function () use ($programs) {
                            $csv = fopen('php://output', 'w');
                            fputcsv($csv, ['Program Name', 'Barangay', 'Category', 'Date']);
                            foreach ($programs as $program) {
                                fputcsv($csv, [$program->name, $program->barangay->name, $program->category->name, $program->program_date]);
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

                                    \Filament\Forms\Components\TextInput::make('program_date')
                                        ->label('Date')
                                        ->default($record->program_date)
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
                        $users = \App\Models\Patient::where('barangay_id', $program->barangay_id)->get();

                        // $smsService = app(\App\Services\SemaphoreService::class);
                        $smsService = app(\App\Services\PhilSMSService::class);

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
                            $message = "Hi {$user->first_name}! \n\n";
                            $message .= "New Health Program: {$program->name} \n";
                            $message .= "Date: {$programDate} \n";
                            $message .= "Time: {$startTime} - {$endTime} \n";
                            
                            if (!empty($program->description)) {
                                $description = strlen($program->description) > 100 
                                    ? substr($program->description, 0, 100) . '...' 
                                    : $program->description;
                                $message .= "\n{$description}\n";
                            }
                            
                            $message .= "\nPlease attend. Thank you!";

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