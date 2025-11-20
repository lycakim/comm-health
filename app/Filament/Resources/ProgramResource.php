<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Enums\RoleEnum;
use App\Models\Patient;
use App\Models\Program;
use App\Models\Barangay;
use App\Models\Category;
use Carbon\Carbon;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Traits\HasUserTypeUrls;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use App\Services\SemaphoreService;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Resources\Components\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\ProgramResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProgramResource\RelationManagers;
use Filament\Infolists\Components\Section as InfolistSection;

class ProgramResource extends Resource
{
    use HasUserTypeUrls;
    
    protected static ?string $model = Program::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        $user = self::currentUser();
        if ($user->isBHW() || $user->isMidwife()) {
            return 'Programs';
        }
        return 'Utility';
    }

    public static function getNavigationSort(): ?int
    {
        $user = self::currentUser();
        if ($user->isMHO()) {
            return 2;
        }
        else if ($user->isBHW() || $user->isMidwife()) {
            return 1;
        }
        
        return 4;
    }

    public static function getPluralModelLabel(): string
    {
        if (self::currentUser()->isMHO()) {
            return 'Health Programs';
        }
        return 'Programs';
    }

    // Full access to the resource (all roles that can see it)
    public static function canAccess(): bool
    {
        return in_array(self::currentUser()->role, [
            RoleEnum::ADMIN,
            RoleEnum::MHO,
            RoleEnum::BHW,      // Can access but read-only
            RoleEnum::MIDWIFE   // Can access and edit
        ]);
    }

    // Can view individual records
    public static function canView(Model $record): bool
    {
        return in_array(self::currentUser()->role, [
            RoleEnum::ADMIN,
            RoleEnum::MHO,
            RoleEnum::BHW,
            RoleEnum::MIDWIFE
        ]);
    }

    // Can edit (excludes BHW)
    public static function canEdit(Model $record): bool
    {
        return in_array(self::currentUser()->role, [
            RoleEnum::ADMIN,
            RoleEnum::MHO,
            RoleEnum::MIDWIFE
        ]);
    }

    // Can delete (excludes BHW & MIDWIFE)
    public static function canDelete(Model $record): bool
    {
        return in_array(self::currentUser()->role, [
            RoleEnum::ADMIN,
            RoleEnum::MHO
        ]);
    }

    // Can create (excludes BHW & MIDWIFE)
    public static function canCreate(): bool
    {
        return in_array(self::currentUser()->role, [
            RoleEnum::ADMIN,
            RoleEnum::MHO
        ]);
    }

    public static function form(Form $form): Form
    {
        $isReadOnly = self::currentUser()->role === RoleEnum::BHW;
        
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->label('Program Name')
                            ->required()
                            ->disabled($isReadOnly),
                        Select::make('category_id')
                            ->label('Category')
                            ->searchable()
                            ->options(Category::query()->get()->pluck('name', 'id')->toArray())
                            ->required()
                            ->disabled($isReadOnly),
                        Select::make('barangay_id')
                            ->label('Barangay')
                            ->searchable()
                            ->options(Barangay::query()->get()->pluck('name', 'id')->toArray())
                            ->required()
                            ->disabled($isReadOnly),
                        Textarea::make('description')
                            ->columnSpanFull()
                            ->disabled($isReadOnly),
                        DatePicker::make('program_date')
                            ->required()
                            ->disabled($isReadOnly),
                        TimePicker::make('program_start_time')
                            ->required()
                            ->disabled($isReadOnly),
                        TimePicker::make('program_end_time')
                            ->required()
                            ->disabled($isReadOnly),
                        
                        Select::make('coordinator')
                            ->label('Coordinator')
                            ->options(function () {
                                return User::where('role', RoleEnum::MHO)->pluck('name', 'id')->toArray();
                            })
                            ->default(function () {
                                return User::where('role', RoleEnum::MHO)->value('id');
                            })
                            ->selectablePlaceholder(false) // This will auto-select first option
                            ->disabled(true)
                            ->required(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Program Information')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Program Name'),
                        TextEntry::make('category.name')
                            ->label('Category'),
                        TextEntry::make('barangay.name')
                            ->label('Barangay'),
                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                        TextEntry::make('program_date')
                            ->label('Program Date')
                            ->date(),
                        TextEntry::make('program_start_time')
                            ->label('Start Time')
                            ->time(),
                        TextEntry::make('program_end_time')
                            ->label('End Time')
                            ->time(),
                        TextEntry::make('coordinatorUser.name')
                            ->label('Coordinator'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        $isBHWOrMidwife = in_array(self::currentUser()->role, [RoleEnum::BHW, RoleEnum::MIDWIFE]);
        
        return $table
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
                Tables\Actions\Action::make('export_csv')
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
                // Show View action for BHW and Midwife
                Tables\Actions\ViewAction::make()
                    ->visible(fn () => $isBHWOrMidwife),
                // Show Edit action for those who can edit
                Tables\Actions\EditAction::make()
                    ->visible(fn () => !$isBHWOrMidwife),
                Tables\Actions\DeleteAction::make(),
                // button for sending sms to all patients in the barangay
                Tables\Actions\Action::make('send_sms')
                    ->label('Send SMS')
                    ->modalHeading('Send SMS Notification')
                    ->modalSubmitActionLabel('Confirm & Send SMS')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn () => Auth::user()->isBHW())

                    ->form(function ($record) {
                        $users = \App\Models\Patient::where('barangay_id', $record->barangay_id)->get();

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

                        $smsService = app(\App\Services\SemaphoreService::class);

                        $successCount = 0;
                        $failCount = 0;
                        $invalidNumbers = []; // Will store patients with invalid/missing numbers
                        $failedNumbers = []; // Will store patients with failed SMS sends

                        // Format program date and time
                        $programDate = $program->program_date ? Carbon::parse($program->program_date)->format('F d, Y') : 'TBA';
                        $startTime = $program->program_start_time ? Carbon::parse($program->program_start_time)->format('g:i A') : 'TBA';
                        $endTime = $program->program_end_time ? Carbon::parse($program->program_end_time)->format('g:i A') : 'TBA';

                        foreach ($users as $user) {
                            // Skip if contact number is empty
                            if (empty($user->contact_number)) {
                                $invalidNumbers[] = $user->first_name . ' ' . $user->last_name;
                                $failCount++;
                                continue;
                            }

                            // Create personalized message with program details
                            $message = "Hi {$user->first_name}! <br><br>";
                            $message .= "New Health Program: {$program->name} <br>";
                            $message .= "Date: {$programDate} <br>";
                            $message .= "Time: {$startTime} - {$endTime} <br>";
                            
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
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $query->latest();
            });
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrograms::route('/'),
            'create' => Pages\CreateProgram::route('/create'),
            'view' => Pages\ViewProgram::route('/{record}'),
            'edit' => Pages\EditProgram::route('/{record}/edit'),
        ];
    }

    // initialize auth user
    public static function currentUser(): ?User
    {
        return Auth::user();
    }
}