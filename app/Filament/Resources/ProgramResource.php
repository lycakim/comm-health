<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Enums\RoleEnum;
use App\Models\Patient;
use App\Models\Program;
use App\Models\Barangay;
use App\Models\Category;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Traits\HasUserTypeUrls;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use App\Exports\ProgramsExport;
use App\Services\SemaphoreService;
use Filament\Forms\Components\Grid;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Resources\Components\Tab;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\Actions\Action;
use App\Filament\Resources\ProgramResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProgramResource\RelationManagers;
use Filament\Infolists\Components\Section as InfolistSection;

class ProgramResource extends Resource
{
    // use HasUserTypeUrls;
    
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
        $user = self::currentUser();
        if (in_array($user->role, [RoleEnum::ADMIN, RoleEnum::MHO])) {
            return true;
        }
        // BHW/Midwife: must have assigned barangay
        if (in_array($user->role, [RoleEnum::BHW, RoleEnum::MIDWIFE])) {
            $barangayId = $user->barangay_id ?? $user->barangays()->first()?->id;
            return !empty($barangayId);
        }
        return false;
    }

    // Can view individual records
    public static function canView(Model $record): bool
    {
        $user = self::currentUser();
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isMHO()) {
            // MHO cannot see BHW-created programs
            $creator = $record->createdByUser ?? $record->coordinatorUser;
            return $creator && in_array($creator->role, [RoleEnum::ADMIN, RoleEnum::MHO]);
        }
        if ($user->isBHW() || $user->isMidwife()) {
            $barangayId = $user->barangay_id ?? $user->barangays()->first()?->id;
            return $barangayId && $record->barangay_id == $barangayId;
        }
        return false;
    }

    // Can edit: Admin/MHO all; BHW only programs they created
    public static function canEdit(Model $record): bool
    {
        $user = self::currentUser();
        if ($user->isAdmin() || $user->isMHO()) {
            return true;
        }
        if ($user->isMidwife()) {
            return true;
        }
        if ($user->isBHW()) {
            $creatorId = $record->created_by ?? $record->coordinator;
            return $creatorId && (int) $creatorId === (int) $user->id;
        }
        return false;
    }

    // Can delete: Admin/MHO all; BHW only programs they created
    public static function canDelete(Model $record): bool
    {
        $user = self::currentUser();
        if ($user->isAdmin() || $user->isMHO()) {
            return true;
        }
        if ($user->isBHW() || $user->isMidwife()) {
            $creatorId = $record->created_by ?? $record->coordinator;
            return $creatorId && (int) $creatorId === (int) $user->id;
        }
        return false;
    }

    // Can create: Admin, MHO, BHW (with barangay)
    public static function canCreate(): bool
    {
        $user = self::currentUser();
        if (in_array($user->role, [RoleEnum::ADMIN, RoleEnum::MHO])) {
            return true;
        }
        if (in_array($user->role, [RoleEnum::BHW, RoleEnum::MIDWIFE])) {
            $barangayId = $user->barangay_id ?? $user->barangays()->first()?->id;
            return !empty($barangayId);
        }
        return false;
    }

    public static function form(Form $form): Form
    {
        $user = self::currentUser();
        $isBHWOrMidwife = in_array($user->role, [RoleEnum::BHW, RoleEnum::MIDWIFE]);
        
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->label('Program Name')
                            ->required(),
                        Select::make('category_id')
                            ->label('Category')
                            ->searchable()
                            ->options(Category::query()->get()->pluck('name', 'id')->toArray())
                            ->required(),
                        Select::make('barangay_id')
                            ->label('Barangay')
                            ->columnSpanFull()
                            ->searchable()
                            ->options(Barangay::query()->get()->pluck('name', 'id')->toArray())
                            ->required()
                            ->disabled($isBHWOrMidwife)
                            ->dehydrated()
                            ->default(fn () => $isBHWOrMidwife ? ($user->barangay_id ?? $user->barangays()->first()?->id) : null)
                            ->afterStateHydrated(function (Select $component) use ($isBHWOrMidwife, $user) {
                                if ($isBHWOrMidwife && empty($component->getState())) {
                                    $component->state($user->barangay_id ?? $user->barangays()->first()?->id);
                                }
                            }),
                        Textarea::make('description')
                            ->columnSpanFull(),
                        DatePicker::make('program_start_date')
                            ->required()
                            ->native(false)
                            ->displayFormat('M d, Y')
                            ->minDate(now())
                            ->firstDayOfWeek(7),
                        DatePicker::make('program_end_date')
                            ->required()
                            ->native(false)
                            ->displayFormat('M d, Y')
                            ->minDate(now())
                            ->firstDayOfWeek(7),
                        TimePicker::make('program_start_time')
                            ->required(),
                        TimePicker::make('program_end_time')
                            ->required(),
                        
                        Select::make('coordinator')
                            ->label('Coordinator')
                            ->columnSpanFull()
                            ->options(function () {
                                return User::whereIn('role', [RoleEnum::MHO, RoleEnum::ADMIN])->pluck('name', 'id')->toArray();
                            })
                            ->default(fn () => Auth::id())
                            ->afterStateHydrated(function (Select $component, $state) {
                                if (empty($state)) {
                                    $component->state(Auth::id());
                                }
                            })
                            ->disabled()
                            ->dehydrated()
                            ->required(fn () => !$isBHWOrMidwife)
                            ->visible(!$isBHWOrMidwife),
                    ])
                    ->columns(2),
                Section::make('Reporting Fields')
                    ->description('Define custom fields that will appear in the consultation form')
                    ->schema([
                        Repeater::make('report_field')
                            ->label('Fields')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Field Name')
                                    ->helperText('Internal identifier (use snake_case, e.g., patient_experience)')
                                    ->required()
                                    ->regex('/^[a-z_]+$/')
                                    ->validationMessages([
                                        'regex' => 'Field name must be lowercase letters and underscores only',
                                    ])
                                    ->columnSpan(1),
                                
                                TextInput::make('label')
                                    ->label('Field Label')
                                    ->helperText('Label shown to users')
                                    ->required()
                                    ->columnSpan(1),
                                
                                Select::make('type')
                                    ->label('Field Type')
                                    ->options([
                                        'text' => 'Text',
                                        'textarea' => 'Textarea',
                                        'number' => 'Number',
                                        'date' => 'Date',
                                        'time' => 'Time',
                                        'datetime' => 'Date & Time',
                                        'select' => 'Select Dropdown',
                                        'checkbox' => 'Checkbox',
                                        'radio' => 'Radio Buttons',
                                        'toggle' => 'Toggle',
                                    ])
                                    ->required()
                                    ->reactive()
                                    ->columnSpan(1),
                                
                                // Show options field only for select and radio types
                                KeyValue::make('options')
                                    ->label('Options')
                                    ->helperText('Key-value pairs for dropdown/radio options')
                                    ->keyLabel('Value')
                                    ->valueLabel('Label')
                                    ->visible(fn (callable $get) => in_array($get('type'), ['select', 'radio']))
                                    ->required(fn (callable $get) => in_array($get('type'), ['select', 'radio']))
                                    ->columnSpanFull(),
                                
                                Textarea::make('helper_text')
                                    ->label('Helper Text')
                                    ->helperText('Optional help text shown below the field')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                
                                Grid::make(3)
                                    ->schema([
                                        Toggle::make('required')
                                            ->label('Required Field')
                                            ->default(false),
                                        
                                        TextInput::make('placeholder')
                                            ->label('Placeholder')
                                            ->visible(fn (callable $get) => in_array($get('type'), ['text', 'textarea', 'number'])),
                                        
                                        TextInput::make('rows')
                                            ->label('Rows')
                                            ->numeric()
                                            ->default(4)
                                            ->visible(fn (callable $get) => $get('type') === 'textarea'),
                                    ]),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Add Field')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? $state['name'] ?? null)
                                ->deleteAction(
                                fn (Action $action) => $action->requiresConfirmation()
                            ),
                    ])
                    ->collapsible()
                    ->visible(!$isBHWOrMidwife),
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
                        TextEntry::make('program_start_date')
                            ->label('Program Start Date')
                            ->date(),
                        TextEntry::make('program_end_date')
                            ->label('Program End Date')
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
        $user = self::currentUser();
        $isBHWOrMidwife = in_array($user->role, [RoleEnum::BHW, RoleEnum::MIDWIFE]);
        $isAdmin = $user->isAdmin();
        
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('barangay.name')
                    ->label('Assigned Barangay')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable(),
                TextColumn::make('createdByUser.name')
                    ->label('Created By')
                    ->formatStateUsing(fn ($state, $record) => $record->createdByUser?->name ?? $record->coordinatorUser?->name ?? 'N/A')
                    ->visible($isAdmin),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_csv')
                    ->label('Export CSV')
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
                        $user = Auth::user();
                        $barangay = $user->barangay_id ? \App\Models\Barangay::find($user->barangay_id) : null;

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
                                new ProgramsExport($programs, $title, $barangay, $user->getPreparedByLabelForExport()),
                                'health-programs_' . date('Y-m-d_His') . '.xlsx',
                                \Maatwebsite\Excel\Excel::XLSX
                            );
                        }

                        // Handle CSV export
                        return response()->streamDownload(function () use ($programs, $title, $barangay) {
                            $csv = fopen('php://output', 'w');
                            $user = Auth::user();
                            $barangay = $user->barangay_id ? \App\Models\Barangay::find($user->barangay_id) : null;
                            $barangayName = $barangay ? $barangay->name : 'All Barangays';
                            $province = config('app.province', 'DAVAO DEL NORTE');
                            $municipality = config('app.municipality', 'CARMEN');
                            $dateTime = now()->format('F d, Y h:i A');
                            
                            // Add UTF-8 BOM for Excel compatibility
                            fprintf($csv, chr(0xEF).chr(0xBB).chr(0xBF));
                            
                            // Add header rows (matching xlsx format)
                            fputcsv($csv, ['REPUBLIC OF THE PHILIPPINES', '', '', '', '']);
                            fputcsv($csv, ['PROVINCE OF ' . strtoupper($province), '', '', '', '']);
                            fputcsv($csv, ['MUNICIPAL HEALTH OFFICE', '', '', '', '']);
                            fputcsv($csv, ['MUNICIPALITY OF ' . strtoupper($municipality), '', '', '', '']);
                            fputcsv($csv, ['BARANGAY ' . strtoupper($barangayName), '', '', '', '']);
                            fputcsv($csv, [strtoupper($title), '', '', '', '']);
                            fputcsv($csv, ['', '', '', '', '']); // Empty row
                            fputcsv($csv, ['As of : ' . $dateTime, '', '', '', '']);
                            
                            // Column headers
                            fputcsv($csv, ['Program Name', 'Barangay', 'Category', 'Date']);
                            
                            foreach ($programs as $program) {
                                fputcsv($csv, [
                                    $program->name, 
                                    $program->barangay->name ?? 'N/A', 
                                    $program->category->name ?? 'N/A', 
                                    $program->program_start_date ? \Carbon\Carbon::parse($program->program_start_date)->format('Y-m-d') : 'N/A'
                                ]);
                            }
                            
                            // Footer row
                            fputcsv($csv, ['', '', '', '']); // Empty row
                            fputcsv($csv, ['Total Records: ' . count($programs), '', '', '']);
                            
                            fclose($csv);
                        }, 'health-programs_' . date('Y-m-d_His') . '.csv');
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn () => Auth::user()->isMHO() || Auth::user()->isAdmin()),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('send_sms')
                    ->label('Send SMS')
                    ->modalHeading('Send SMS Notification')
                    ->modalSubmitActionLabel('Confirm & Send SMS')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(function ($record) {
                        $user = Auth::user();
                        $creator = $record->createdByUser ?? $record->coordinatorUser;

                        if ($user->isMHO() || $user->isAdmin()) {
                            return !$creator || in_array($creator->role, [RoleEnum::ADMIN, RoleEnum::MHO]);
                        }

                        if ($user->isBHW() || $user->isMidwife()) {
                            return $creator && in_array($creator->role, [RoleEnum::BHW, RoleEnum::MIDWIFE]);
                        }

                        return false;
                    })

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

                        $programDate = $program->program_start_date ? Carbon::parse($program->program_start_date)->format('F d, Y') : 'TBA';
                        $startTime = $program->program_start_time ? Carbon::parse($program->program_start_time)->format('g:i A') : 'TBA';
                        $endTime = $program->program_end_time ? Carbon::parse($program->program_end_time)->format('g:i A') : 'TBA';

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
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                $query->with(['createdByUser', 'coordinatorUser']);

                if ($user->isAdmin()) {
                    return $query->latest();
                }
                if ($user->isMHO()) {
                    // MHO: only programs created by Admin or MHO (exclude BHW/Midwife-created)
                    return $query->where(function ($q) {
                        $q->whereHas('createdByUser', fn ($sub) => $sub->whereIn('role', [RoleEnum::ADMIN, RoleEnum::MHO]))
                            ->orWhere(function ($sub) {
                                $sub->whereNull('created_by')
                                    ->whereHas('coordinatorUser', fn ($c) => $c->whereIn('role', [RoleEnum::ADMIN, RoleEnum::MHO]));
                            })
                            ->orWhereNull('created_by');
                    })->latest();
                }
                if ($user->isBHW() || $user->isMidwife()) {
                    $barangayId = $user->barangay_id ?? $user->barangays()->first()?->id;
                    return $query->where('barangay_id', $barangayId)->latest();
                }
                return $query->latest();
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