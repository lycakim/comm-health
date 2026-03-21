<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\Report;
use App\Enums\RoleEnum;
use App\Models\Patient;
use Filament\Pages\Page;
use Filament\Tables\Table;
use App\Models\Consultation;
use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use App\Exports\GenericReportExport;
use App\Services\PDFGenerationService;
use Filament\Resources\Components\Tab;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Grid;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Group;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Filament\Infolists\Components\Section;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\Action as TablesAction;
use App\Filament\Concerns\HasDashboardBreadcrumb;

class Reports extends Page implements HasTable
{
    use HasDashboardBreadcrumb;
    use InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';

    protected static string $view = 'filament.pages.reports';

    protected static ?string $navigationGroup = 'Data  & Reports';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return in_array(self::currentUser()->role, [
            // RoleEnum::ADMIN,
            // RoleEnum::MHO,
            // RoleEnum::BHW,
        ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_report')
                ->label('Generate Report')
                ->disabled(fn() => is_null(Auth::user()->barangay_id) || Auth::user()->role === RoleEnum::MHO->value)
                ->icon('heroicon-o-plus')
                ->form([
                    Select::make('report_type')
                        ->label('Report Type')
                        ->options([
                            '2022_family_household_profile_report' => '2022 Family/Household Profile Report',
                            'masterlist_of_person_with_disability' => 'Masterlist of Person with Disability',
                            'non_communicable_disease_masterlist_senior_citizen_pwds' => 'Non Communicable Disease Masterlist (Senior Citizen/PWDs)',
                            'expanded_program_immunization' => 'Expanded Program Immunization (EPI)',
                            'immunization' => 'Immunization',
                        ])
                        ->required()
                        ->searchable()
                        ->live()
                        ->helperText('Select the type of report you want to generate'),
                ])
                ->action(function (array $data, PDFGenerationService $pdfService) {
                    try {
                        $reportType = $data['report_type'];

                        // Generate the PDF
                        $pdf = $pdfService->generateReport($reportType);
                        
                        // Get the filename
                        $filename = $pdfService->getReportFilename($reportType);
                        
                        // Log the report generation
                        $pdfService->logReportGeneration($reportType, true);
                        
                        // Show success notification
                        Notification::make()
                            ->title('Report Generated Successfully')
                            ->success()
                            ->body('Your PDF report is ready for download.')
                            ->send();

                        // Return the PDF as download
                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            $filename,
                            ['Content-Type' => 'application/pdf']
                        );
                    } catch (\Exception $e) {
                        // Log the failed attempt
                        if (isset($pdfService, $reportType)) {
                            $pdfService->logReportGeneration($reportType, false);
                        }
                        
                        // Show error notification
                        Notification::make()
                            ->title('Error Generating Report')
                            ->danger()
                            ->body($e->getMessage())
                            ->persistent()
                            ->send();
                        
                        // Log the exception
                        logger()->error('PDF Report Generation Failed', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'data' => $data ?? null,
                        ]);
                    }
                })
                ->modalHeading('Generate Report')
                ->modalDescription('Select the report type and other details.') 
                ->modalSubmitActionLabel('Generate')
                ->modalWidth('xl')
                ->closeModalByClickingAway(false)
                ->requiresConfirmation(false)
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'View and download reports';
    }

    protected function getGeneratedReportsTable(): Table
    {
        return $this->makeTable()
            ->query($this->getGeneratedReportsTableQuery())
            ->heading('Generated Reports')
            ->columns([
                TextColumn::make('title')
                    ->label('Report Title')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                
                TextColumn::make('report_type')
                    ->label('Report Type')
                    ->formatStateUsing(fn (string $state) => ucwords(str_replace('-', ' ', $state)))
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('format')
                    ->label('Format')
                    ->badge()
                    ->color(fn (string $state) => $state === 'pdf' ? 'success' : 'warning')
                    ->formatStateUsing(fn (string $state) => strtoupper($state))
                    ->sortable(),
                
                TextColumn::make('user.name')
                    ->label('Generated By')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Unknown'),
                
                TextColumn::make('barangay.name')
                    ->label('Barangay')
                    ->searchable()
                    ->sortable()
                    ->placeholder('All Barangays')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('formatted_file_size')
                    ->label('File Size')
                    ->getStateUsing(fn (Report $record) => $record->formatted_file_size)
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('file_size', $direction)),
                
                TextColumn::make('created_at')
                    ->label('Generated Date')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                SelectFilter::make('report_type')
                    ->label('Report Type')
                    ->options([
                        'resident-profiling' => 'Patient Profiling',
                        'maternal-child' => 'Maternal and Child',
                        'senior-citizens' => 'Senior Citizens',
                        'family-planning' => 'Family Planning',
                        'morbidity-mortality' => 'Morbidity and Mortality',
                    ])
                    ->multiple(),
                
                SelectFilter::make('format')
                    ->label('Format')
                    ->options([
                        'pdf' => 'PDF',
                        'csv' => 'CSV',
                    ])
                    ->multiple(),
            ])
            ->actions([
                TableAction::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (Report $record) {
                        if (!$record->fileExists()) {
                            Notification::make()
                                ->title('File Not Found')
                                ->body('The report file could not be found in storage.')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        $filePath = Storage::disk('public')->path($record->file_path);
                        $mimeType = match ($record->format) {
                            'pdf' => 'application/pdf',
                            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            default => 'text/csv',
                        };
                        
                        return response()->download($filePath, $record->file_name, [
                            'Content-Type' => $mimeType,
                        ]);
                    }),
                
                TableAction::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn (Report $record) => $record->format === 'pdf')
                    ->url(fn (Report $record) => $record->fileExists() ? Storage::disk('public')->url($record->file_path) : null)
                    ->openUrlInNewTab(),
                
                TableAction::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Report')
                    ->modalDescription('Are you sure you want to delete this report? This action cannot be undone.')
                    ->modalSubmitActionLabel('Delete')
                    ->action(function (Report $record) {
                        try {
                            // Delete file from storage
                            if ($record->file_path && Storage::disk('public')->exists($record->file_path)) {
                                Storage::disk('public')->delete($record->file_path);
                            }
                            
                            // Delete database record
                            $record->delete();
                            
                            Notification::make()
                                ->title('Report Deleted')
                                ->body('The report has been successfully deleted.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error Deleting Report')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                // Delete file from storage
                                if ($record->file_path && Storage::disk('public')->exists($record->file_path)) {
                                    Storage::disk('public')->delete($record->file_path);
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->emptyStateHeading('No generated reports yet')
            ->emptyStateDescription('Generated reports will appear here once you create them from the Report Template tab.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    protected function getGeneratedReportsTableQuery()
    {
        return Report::with('user', 'barangay')
            ->whereNotNull('report_type')
            ->whereNotNull('file_path')
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
            ->latest();
    }

    public function getGeneratedReportsTableProperty()
    {
        return $this->getGeneratedReportsTable();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Consultation Reports')
            ->query(fn () => Consultation::with('program', 'patient')
                ->whereNotNull('program_id')
                ->where('program_id', '>', 0)
                ->when(
                    in_array(Auth::user()->role, [RoleEnum::BHW, RoleEnum::MIDWIFE]),
                    function ($query) {
                        $barangayId = Auth::user()->barangay_id;
                        if ($barangayId) {
                            $query->whereHas('patient', function ($patientQuery) use ($barangayId) {
                                $patientQuery->where('barangay_id', $barangayId);
                            });
                        } else {
                            $query->whereRaw('1 = 0');
                        }
                    }
                )
                ->latest()
            )
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                
                TextColumn::make('program.name')
                    ->label('Program')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->placeholder('No Program'),
                
                TextColumn::make('patient.first_name')
                    ->label('Patient')
                    ->formatStateUsing(fn (Consultation $record) => 
                        trim("{$record->patient?->first_name} {$record->patient?->last_name}")
                    )
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->placeholder('No Patient'),
                
                TextColumn::make('date')
                    ->label('Consultation Date')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->placeholder('No Date'),
            ])
            ->filters([
                SelectFilter::make('program_id')
                    ->label('Program')
                    ->relationship('program', 'name')
                    ->preload(),
            ])
            ->headerActions([
                TablesAction::make('generate_data_report')
                    ->label('Generate Data Report')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->disabled(fn() => is_null(Auth::user()->barangay_id) || Auth::user()->role === RoleEnum::MHO->value)
                    ->color('gray')
                    ->form([
                        Select::make('report_type')
                            ->label('Report Type')
                            ->options([
                                'resident-profiling' => 'Resident Profiling',
                                'maternal-child' => 'Maternal and Child Report',
                                'senior-citizens' => 'Senior Citizens Report',
                                'family-planning' => 'Family Planning Report',
                                'morbidity-mortality' => 'Morbidity and Mortality Report',
                                'family-profile-consolidation' => 'Family Profile Consolidation',
                            ])
                            ->required()
                            ->default('resident-profiling'),
                        Select::make('format')
                            ->label('Export Format')
                            ->options([
                                'xlsx' => 'Excel (XLSX)',
                                'pdf' => 'PDF (Document)',
                            ])
                            ->default('xlsx')
                            ->required()
                    ])
                    ->action(function ($data, $livewire) {
                        return $livewire->generateReport($data['report_type'], $data['format']);
                    }),
            ])
            ->actions([
                TableAction::make('view')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (Consultation $record) => 'Consultation Report - ' . $record->program?->name)
                    ->modalWidth('5xl')
                    ->infolist(fn (Consultation $record): array => [
                        Section::make('Program Information')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('program.name')
                                            ->label('Program')
                                            ->badge()
                                            ->color('success')
                                            ->weight(FontWeight::Bold),
                                        
                                        TextEntry::make('date')
                                            ->label('Consultation Date')
                                            ->date('F d, Y')
                                            ->icon('heroicon-o-calendar'),
                                        
                                        TextEntry::make('id')
                                            ->label('Consultation ID')
                                            ->badge()
                                            ->color('gray'),
                                    ]),
                            ])
                            ->columns(1),
                        
                        Section::make('Patient Information')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('patient.first_name')
                                            ->label('First Name')
                                            ->formatStateUsing(fn ($record) => $record->patient?->first_name ?? 'N/A'),
                                        
                                        TextEntry::make('patient.middle_name')
                                            ->label('Middle Name')
                                            ->formatStateUsing(fn ($record) => $record->patient?->middle_name ?? 'N/A'),
                                        
                                        TextEntry::make('patient.last_name')
                                            ->label('Last Name')
                                            ->formatStateUsing(fn ($record) => $record->patient?->last_name ?? 'N/A'),
                                    ]),
                            ])
                            ->columns(1)
                            ->collapsible(),
                        
                        Section::make('Health Information')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('disability')
                                            ->label('With Disability')
                                            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                                            ->badge()
                                            ->color(fn ($state) => $state ? 'warning' : 'success'),
                                        
                                        TextEntry::make('philhealth')
                                            ->label('With Philhealth')
                                            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                                            ->badge()
                                            ->color(fn ($state) => $state ? 'success' : 'gray'),
                                        
                                        TextEntry::make('member_of_4ps')
                                            ->label('4Ps Member')
                                            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                                            ->badge()
                                            ->color(fn ($state) => $state ? 'info' : 'gray'),
                                    ]),
                                
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('weight')
                                            ->label('Weight (kg)')
                                            ->suffix(' kg')
                                            ->placeholder('Not recorded'),
                                        
                                        TextEntry::make('height')
                                            ->label('Height (cm)')
                                            ->suffix(' cm')
                                            ->placeholder('Not recorded'),
                                        
                                        TextEntry::make('purok.name')
                                            ->label('Purok')
                                            ->placeholder('Not specified'),
                                    ]),
                            ])
                            ->columns(1)
                            ->collapsible(),
                        
                        Section::make('Report Field Responses')
                            ->schema([
                                Group::make()
                                    ->schema(function (Consultation $record) {
                                        $responses = $record->report_field_response ?? [];
                                        $reportFields = $record->program?->report_field ?? [];
                                        
                                        if (empty($responses)) {
                                            return [
                                                TextEntry::make('no_responses')
                                                    ->label('')
                                                    ->default('No report field responses recorded')
                                                    ->color('gray'),
                                            ];
                                        }
                                        
                                        $fieldLabels = collect($reportFields)->pluck('label', 'name')->toArray();
                                        $fieldTypes = collect($reportFields)->pluck('type', 'name')->toArray();
                                        
                                        return collect($responses)->map(function ($value, $key) use ($fieldLabels, $fieldTypes) {
                                            $label = $fieldLabels[$key] ?? ucwords(str_replace('_', ' ', $key));
                                            $type = $fieldTypes[$key] ?? 'text';
                                            
                                            $entry = TextEntry::make("report_field_response.{$key}")
                                                ->label($label)
                                                ->formatStateUsing(function ($state) use ($type) {
                                                    if (is_bool($state)) {
                                                        return $state ? 'Yes' : 'No';
                                                    }
                                                    if (is_array($state)) {
                                                        return implode(', ', $state);
                                                    }
                                                    if (empty($state)) {
                                                        return 'Not provided';
                                                    }
                                                    return $state;
                                                });
                                            
                                            // Add styling based on field type
                                            if ($type === 'textarea') {
                                                $entry->columnSpanFull();
                                            }
                                            
                                            if (in_array($type, ['checkbox', 'toggle'])) {
                                                $entry->badge()
                                                    ->color(fn ($state) => $state ? 'success' : 'gray');
                                            }
                                            
                                            return $entry;
                                        })->toArray();
                                    })
                                    ->columns(2),
                            ])
                            ->columns(1)
                            ->collapsed(false),
                        
                        Section::make('Additional Details')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('user.name')
                                            ->label('Recorded By')
                                            ->icon('heroicon-o-user')
                                            ->placeholder('Not specified'),
                                        
                                        TextEntry::make('created_at')
                                            ->label('Created At')
                                            ->dateTime('F d, Y h:i A')
                                            ->icon('heroicon-o-clock'),
                                    ]),
                            ])
                            ->columns(1)
                            ->collapsible()
                            ->collapsed(),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->slideOver()
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->emptyStateHeading('No consultation reports yet')
            ->emptyStateDescription('Consultation reports will appear here once created.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    // initialize auth user
    public static function currentUser(): ?User
    {
        return Auth::user();
    }

    protected static function getDynamicReportColumns(): array
    {
        // Get all unique field names from programs
        $allFields = \App\Models\Program::whereNotNull('report_field')
            ->get()
            ->pluck('report_field')
            ->flatten(1)
            ->unique('name');
        
        return $allFields->map(function ($field) {
            return TextColumn::make('report_field_response.' . $field['name'])
                ->label($field['label'] ?? ucwords(str_replace('_', ' ', $field['name'])))
                ->formatStateUsing(function ($state) {
                    if (is_bool($state)) {
                        return $state ? 'Yes' : 'No';
                    }
                    if (is_array($state)) {
                        return implode(', ', $state);
                    }
                    return $state ?? '-';
                })
                ->toggleable(isToggledHiddenByDefault: true)
                ->wrap();
        })->toArray();
    }

    /**
     * Open modal to generate report (called from Report Template tab buttons)
     */
    public function openGenerateReportModal(string $reportType, string $frequency = 'monthly')
    {
        return $this->generateReport($reportType, 'pdf');
    }

    /**
     * Preview a report without generating/downloading
     */
    public function previewReport(string $reportType)
    {
        try {
            // Normalize report type (handle both patient-profiling and resident-profiling)
            $normalizedType = $this->normalizeReportType($reportType);
            
            // Get data based on report type
            $data = $this->getReportData($normalizedType);
            
            // Check if there's no data (skip for family-profile-consolidation which has summary/ageGrouping)
            $hasData = $normalizedType === 'family-profile-consolidation'
                ? isset($data['summary'])
                : (!empty($data['rows']) && count($data['rows']) > 0);
            if (!$hasData) {
                Notification::make()
                    ->title('No Data Available')
                    ->body('There is no data available for this report. Please try a different report type or check back later.')
                    ->warning()
                    ->send();
                return;
            }
            
            // Generate PDF for preview
            $pdfService = app(PDFGenerationService::class);
            $user = Auth::user();
            $barangay = $user->barangay_id ? \App\Models\Barangay::find($user->barangay_id) : null;
            $pdf = $pdfService->generateReportDataPdf($data, $normalizedType, $barangay);
            
            // Create a temporary file for preview
            $tempFilename = 'preview_' . $reportType . '_' . now()->format('YmdHis') . '.pdf';
            $tempPath = storage_path('app/temp/' . $tempFilename);
            
            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            // Save PDF to temp location
            file_put_contents($tempPath, $pdf->output());
            
            // Return download that opens in new tab
            return response()->download($tempPath, $tempFilename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $tempFilename . '"',
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error previewing report')
                ->body($e->getMessage())
                ->danger()
                ->send();
            
            logger()->error('Report Preview Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'report_type' => $reportType ?? null,
            ]);
        }
    }

    /**
     * Generate and save a report
     */
    public function generateReport(string $reportType, string $format = 'pdf')
    {
        try {
            // Normalize report type (handle both patient-profiling and resident-profiling)
            $normalizedType = $this->normalizeReportType($reportType);
            
            // Get data based on report type
            $data = $this->getReportData($normalizedType);
            
            // Check if there's no data (skip for family-profile-consolidation which has summary/ageGrouping)
            $hasData = $normalizedType === 'family-profile-consolidation'
                ? isset($data['summary'])
                : (!empty($data['rows']) && count($data['rows']) > 0);
            if (!$hasData) {
                Notification::make()
                    ->title('No Data Available')
                    ->body('There is no data available for this report. Please try a different report type or check back later.')
                    ->warning()
                    ->send();
                return;
            }
            
            $pdfService = app(PDFGenerationService::class);
            $user = Auth::user();
            $barangay = $user->barangay_id ? \App\Models\Barangay::find($user->barangay_id) : null;
            // Family Profile Consolidation only supports PDF
            if ($normalizedType === 'family-profile-consolidation' && $format !== 'pdf') {
                $format = 'pdf';
            }
            $filename = "{$normalizedType}_" . now()->format('Y-m-d_His') . ".{$format}";
            $fileContent = null;
            
            // Get report title
            $reportTitles = [
                'patient-profiling' => 'Patient Profiling Report',
                'resident-profiling' => 'Patient Profiling Report',
                'maternal-child' => 'Maternal and Child Report',
                'senior-citizens' => 'Senior Citizens Health Status Report',
                'family-planning' => 'Family Planning Usage Report',
                'morbidity-mortality' => 'Morbidity and Mortality Report',
                'family-profile-consolidation' => 'Family Profile Consolidation',
            ];
            $reportTitle = $reportTitles[$normalizedType] ?? ucwords(str_replace('-', ' ', $normalizedType)) . ' Report';
            $barangayName = $barangay ? $barangay->name : 'All Barangays';
            $province = config('app.province', 'DAVAO DEL NORTE');
            $municipality = config('app.municipality', 'CARMEN');
            $dateTime = now()->format('F d, Y h:i A');
            
            // Handle PDF export
            if ($format === 'pdf') {
                $pdf = $pdfService->generateReportDataPdf($data, $normalizedType, $barangay);
                $fileContent = $pdf->output();
            } elseif ($format === 'xlsx') {
                // Handle XLSX export (not supported for family-profile-consolidation)
                if ($normalizedType === 'family-profile-consolidation') {
                    Notification::make()
                        ->title('Format Not Supported')
                        ->body('Family Profile Consolidation report is only available in PDF format.')
                        ->warning()
                        ->send();
                    return;
                }
                $user->load('barangay');
                $export = new GenericReportExport($data['headers'], $data['rows'], $reportTitle, $barangay, $user->getPreparedByLabelForExport());
                $fileContent = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
            } else {
                // Handle CSV export (not supported for family-profile-consolidation)
                if ($normalizedType === 'family-profile-consolidation') {
                    Notification::make()
                        ->title('Format Not Supported')
                        ->body('Family Profile Consolidation report is only available in PDF format.')
                        ->warning()
                        ->send();
                    return;
                }
                $handle = fopen('php://temp', 'r+');
                
                // Add UTF-8 BOM for Excel compatibility
                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // Add header rows (matching xlsx format)
                fputcsv($handle, ['REPUBLIC OF THE PHILIPPINES', '', '', '', '', '', '', '', '', '', '', '', '', '', '']);
                fputcsv($handle, ['PROVINCE OF ' . strtoupper($province), '', '', '', '', '', '', '', '', '', '', '', '', '', '']);
                fputcsv($handle, ['MUNICIPAL HEALTH OFFICE', '', '', '', '', '', '', '', '', '', '', '', '', '', '']);
                fputcsv($handle, ['MUNICIPALITY OF ' . strtoupper($municipality), '', '', '', '', '', '', '', '', '', '', '', '', '', '']);
                fputcsv($handle, ['BARANGAY ' . strtoupper($barangayName), '', '', '', '', '', '', '', '', '', '', '', '', '', '']);
                fputcsv($handle, [strtoupper($reportTitle), '', '', '', '', '', '', '', '', '', '', '', '', '', '']);
                fputcsv($handle, ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '']); // Empty row
                fputcsv($handle, ['As of : ' . $dateTime, '', '', '', '', '', '', '', '', '', '', '', '', '', '']);
                
                // Add column headers
                fputcsv($handle, $data['headers']);
                
                // Add rows
                foreach ($data['rows'] as $row) {
                    fputcsv($handle, $row);
                }
                
                // Add footer row
                fputcsv($handle, ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '']); // Empty row
                fputcsv($handle, ['Total Records: ' . count($data['rows']), '', '', '', '', '', '', '', '', '', '', '', '', '', '']);
                
                rewind($handle);
                $fileContent = stream_get_contents($handle);
                fclose($handle);
            }
            
            // Save to storage
            $fileMetadata = $pdfService->saveReportToStorage($fileContent, $normalizedType, $format, $filename);
            
            // Create database record
            $report = Report::create([
                'title' => $reportTitles[$normalizedType] ?? ucwords(str_replace('-', ' ', $normalizedType)) . ' Report',
                'report_type' => $normalizedType,
                'format' => $format,
                'file_path' => $fileMetadata['file_path'],
                'file_name' => $fileMetadata['file_name'],
                'file_size' => $fileMetadata['file_size'],
                'user_id' => $user->id,
                'barangay_id' => $user->barangay_id,
                'generated_by' => $user->name,
            ]);
            
            // Show success notification
            Notification::make()
                ->title('Report Generated Successfully')
                ->body('The report has been generated and saved. You can download it from the Generated Reports tab.')
                ->success()
                ->send();
            
            // Return download response
            if ($format === 'pdf') {
                return response()->streamDownload(
                    fn () => print($fileContent),
                    $filename,
                    ['Content-Type' => 'application/pdf']
                );
            }
            if ($format === 'xlsx') {
                return Response::streamDownload(
                    fn () => print($fileContent),
                    $filename,
                    [
                        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'Content-Disposition' => "attachment; filename={$filename}",
                    ]
                );
            }
            return Response::streamDownload(
                fn () => print($fileContent),
                $filename,
                [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => "attachment; filename={$filename}",
                ]
            );
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error generating report')
                ->body($e->getMessage())
                ->danger()
                ->send();
            
            logger()->error('Report Generation Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'report_type' => $reportType ?? null,
            ]);
        }
    }

    /**
     * Normalize report type (handle both patient-profiling and resident-profiling)
     */
    protected function normalizeReportType(string $reportType): string
    {
        return match($reportType) {
            'patient-profiling' => 'resident-profiling',
            default => $reportType,
        };
    }

    protected function getReportData(string $reportType)
    {
        return match($reportType) {
            'resident-profiling', 'patient-profiling' => $this->getPatientProfilingData(),
            'maternal-child' => $this->getMaternalChildData(),
            'senior-citizens' => $this->getSeniorCitizensData(),
            'family-planning' => $this->getFamilyPlanningData(),
            'morbidity-mortality' => $this->getMorbidityMortalityData(),
            'family-profile-consolidation' => $this->getFamilyProfileConsolidationData(),
            default => throw new \Exception('Invalid report type: ' . $reportType),
        };
    }

    protected function getPatientProfilingData()
    {
        $patients = Patient::get();
        
        return [
            'headers' => [
                'Resident ID',
                'First Name',
                'Last Name',
                'Date of Birth',
                'Age',
                'Gender',
                'Contact Number',
                'Blood Type',
                'Civil Status',
                'Registration Date'
            ],
            'rows' => $patients->map(function ($patient) {
                return [
                    $patient->id,
                    $patient->first_name,
                    $patient->last_name,
                    $patient->birth_date?->format('Y-m-d'),
                    $patient->age,
                    $patient->sex,
                    $patient->contact_number,
                    $patient->blood_type,
                    $patient->civil_status,
                    $patient->created_at->format('Y-m-d')
                ];
            })->toArray()
        ];
    }

    protected function getMaternalChildData()
    {
        $patients = Patient::where('sex', 'Female')
            ->whereBetween('age', [15, 49])
            ->get();
        
        return [
            'headers' => [
                'Resident ID',
                'Full Name',
                'Age',
                'Contact Number',
                'Number of Pregnancies',
                'Number of Children',
                'Last Prenatal Visit'
            ],
            'rows' => $patients->map(function ($patient) {
                return [
                    $patient->id,
                    $patient->first_name . ' ' . $patient->last_name,
                    $patient->age,
                    $patient->contact_number,
                    $patient->pregnancies?->count() ?? 0,
                    $patient->children?->count() ?? 0,
                    $patient->last_prenatal_visit?->format('Y-m-d') ?? 'N/A'
                ];
            })->toArray()
        ];
    }

    protected function getSeniorCitizensData()
    {
        $patients = Patient::where('age', '>=', 60)
            ->with(['consultations', 'medications'])
            ->get();
        
        return [
            'headers' => [
                'Resident ID',
                'Full Name',
                'Age',
                'Gender',
                'Contact Number',
                'Blood Type',
                'Total Consultations',
                'Active Medications',
                'Last Consultation Date',
                'Health Status'
            ],
            'rows' => $patients->map(function ($patient) {
                return [
                    $patient->id,
                    $patient->first_name . ' ' . $patient->last_name,
                    $patient->age,
                    $patient->sex,
                    $patient->contact_number,
                    $patient->blood_type,
                    $patient->consultations?->count() ?? 0,
                    $patient->medications?->where('status', 'active')->count() ?? 0,
                    $patient->consultations?->first()?->date?->format('Y-m-d') ?? 'N/A',
                    $patient->health_status ?? 'N/A'
                ];
            })->toArray()
        ];
    }

    protected function getFamilyPlanningData()
    {
        $patients = Patient::get();
        
        return [
            'headers' => [
                'Resident ID',
                'Full Name',
                'Age',
                'Contact Number',
                'Method Used',
                'Start Date',
                'Status',
                'Last Follow-up Date',
                'Next Schedule'
            ],
            'rows' => $patients->map(function ($patient) {
                $latestRecord = $patient->familyPlanningRecords?->first();
                return [
                    $patient->id,
                    $patient->first_name . ' ' . $patient->last_name,
                    $patient->age,
                    $patient->contact_number,
                    $latestRecord?->method ?? 'N/A',
                    $latestRecord?->start_date?->format('Y-m-d') ?? 'N/A',
                    $latestRecord?->status ?? 'N/A',
                    $latestRecord?->last_followup?->format('Y-m-d') ?? 'N/A',
                    $latestRecord?->next_schedule?->format('Y-m-d') ?? 'N/A'
                ];
            })->toArray()
        ];
    }

    protected function getFamilyProfileConsolidationData()
    {
        $user = Auth::user();
        $query = Patient::query();
        if ($user->barangay_id) {
            $query->where('barangay_id', $user->barangay_id);
        }
        $patients = $query->get();

        // Summary stats
        $totalPopulation = $patients->count();
        $householdHeads = $patients->whereIn('relationship_to_head_of_family', ['Self', 'Head', 'Household-Head'])->count();
        $totalHouses = $patients->sum('no_of_house') ?: $householdHeads;
        $familyHeads = $householdHeads;
        $married = $patients->whereIn('civil_status', ['married', 'Married'])->count();
        $widowMale = $patients->whereIn('civil_status', ['widowed', 'Widowed'])->where('sex', 'male')->count();
        $widowFemale = $patients->whereIn('civil_status', ['widowed', 'Widowed'])->where('sex', 'female')->count();
        $liveIn = $patients->whereIn('civil_status', ['live-in', 'Live-in'])->count();
        $soloParent = 0; // Not in schema - use placeholder
        $singleMother = $patients->where('sex', 'female')->whereIn('civil_status', ['single', 'Single'])->count(); // Approximation
        $separated = $patients->whereIn('civil_status', ['separated', 'Separated'])->count();
        $pregnantWomen = $patients->where('pregnant', true)->count();
        $wra = $patients->where('sex', 'female')->filter(fn ($p) => $p->age >= 15 && $p->age <= 49)->count();
        $singleWra = $patients->where('sex', 'female')->filter(fn ($p) => $p->age >= 15 && $p->age <= 49)->whereIn('civil_status', ['single', 'Single'])->count();
        $seniorCitizens = $patients->filter(fn ($p) => $p->age >= 60)->count();
        $smokersMale = $patients->where('sex', 'male')->filter(fn ($p) => is_array($p->health_statuses) && in_array('Smoker', $p->health_statuses))->count();
        $smokersFemale = $patients->where('sex', 'female')->filter(fn ($p) => is_array($p->health_statuses) && in_array('Smoker', $p->health_statuses))->count();
        $pwd = $patients->filter(fn ($p) => is_array($p->health_statuses) && in_array('Person with Disabilities', $p->health_statuses))->count();
        $nhtsCct = 0;
        $nhtsNonCct = 0;
        $nhtsSet = 0;
        $ofw = 0;

        // Age grouping: 0-11 mos, 1, 2, 3, ..., 17, 18, 19, 20, 21-24, ..., 85+
        $ageGroupsLeft = [];
        $ageGroupsRight = [];
        $ageLabelsLeft = ['0-11 mos', '1 year', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17'];

        foreach ($ageLabelsLeft as $label) {
            $male = 0;
            $female = 0;
            if ($label === '0-11 mos') {
                $male = $patients->where('sex', 'male')->filter(fn ($p) => $p->age !== null && (int) $p->age < 1)->count();
                $female = $patients->where('sex', 'female')->filter(fn ($p) => $p->age !== null && (int) $p->age < 1)->count();
            } elseif ($label === '1 year') {
                $male = $patients->where('sex', 'male')->filter(fn ($p) => (int) ($p->age ?? 0) === 1)->count();
                $female = $patients->where('sex', 'female')->filter(fn ($p) => (int) ($p->age ?? 0) === 1)->count();
            } else {
                $ageNum = (int) $label;
                $male = $patients->where('sex', 'male')->filter(fn ($p) => (int) ($p->age ?? 0) === $ageNum)->count();
                $female = $patients->where('sex', 'female')->filter(fn ($p) => (int) ($p->age ?? 0) === $ageNum)->count();
            }
            $ageGroupsLeft[] = ['label' => $label, 'male' => $male, 'female' => $female, 'total' => $male + $female];
        }

        $rangesRight = [
            ['18', 18, 18],
            ['19', 19, 19],
            ['20', 20, 20],
            ['21-24', 21, 24],
            ['25-29', 25, 29],
            ['30-34', 30, 34],
            ['35-39', 35, 39],
            ['40-44', 40, 44],
            ['45-49', 45, 49],
            ['50-54', 50, 54],
            ['55-59', 55, 59],
            ['60-64', 60, 64],
            ['65-69', 65, 69],
            ['70-74', 70, 74],
            ['75-79', 75, 79],
            ['80-84', 80, 84],
            ['85+', 85, 999],
        ];
        foreach ($rangesRight as [$label, $min, $max]) {
            $ageMin = $min;
            $ageMax = $max;
            $male = $patients->where('sex', 'male')->filter(fn ($p) => (($age = (int) ($p->age ?? 0)) >= $ageMin && $age <= $ageMax))->count();
            $female = $patients->where('sex', 'female')->filter(fn ($p) => (($age = (int) ($p->age ?? 0)) >= $ageMin && $age <= $ageMax))->count();
            $ageGroupsRight[] = ['label' => $label, 'male' => $male, 'female' => $female, 'total' => $male + $female];
        }

        $totalLeft = [
            'male' => collect($ageGroupsLeft)->sum('male'),
            'female' => collect($ageGroupsLeft)->sum('female'),
        ];
        $totalLeft['total'] = $totalLeft['male'] + $totalLeft['female'];
        $totalRight = [
            'male' => collect($ageGroupsRight)->sum('male'),
            'female' => collect($ageGroupsRight)->sum('female'),
        ];
        $totalRight['total'] = $totalRight['male'] + $totalRight['female'];

        return [
            'summary' => [
                'totalPopulation' => $totalPopulation,
                'householdHeads' => $householdHeads,
                'totalHouses' => $totalHouses,
                'familyHeads' => $familyHeads,
                'married' => $married,
                'widowMale' => $widowMale,
                'widowFemale' => $widowFemale,
                'liveIn' => $liveIn,
                'soloParent' => $soloParent,
                'singleMother' => $singleMother,
                'separated' => $separated,
                'pregnantWomen' => $pregnantWomen,
                'wra' => $wra,
                'singleWra' => $singleWra,
                'seniorCitizens' => $seniorCitizens,
                'smokersMale' => $smokersMale,
                'smokersFemale' => $smokersFemale,
                'nhtsCct' => $nhtsCct,
                'nhtsNonCct' => $nhtsNonCct,
                'nhtsSet' => $nhtsSet,
                'pwd' => $pwd,
                'ofw' => $ofw,
            ],
            'ageGroupsLeft' => $ageGroupsLeft,
            'ageGroupsRight' => $ageGroupsRight,
            'totalLeft' => $totalLeft,
            'totalRight' => $totalRight,
        ];
    }

    protected function getMorbidityMortalityData()
    {
        // Morbidity data
        $morbidityPatients = Patient::get();
        
        // Mortality data
        $mortalityPatients = Patient::where('status', 'deceased')
            ->with(['deathRecord'])
            ->get();
        
        $rows = [];
        
        // Add morbidity records
        foreach ($morbidityPatients as $patient) {
            foreach ($patient->diagnoses as $diagnosis) {
                $rows[] = [
                    'MORBIDITY',
                    $patient->id,
                    $patient->first_name . ' ' . $patient->last_name,
                    $patient->age,
                    $patient->sex,
                    $diagnosis->disease_name ?? 'N/A',
                    $diagnosis->diagnosis_date?->format('Y-m-d') ?? 'N/A',
                    $diagnosis->severity ?? 'N/A',
                    'N/A', // cause of death
                    'N/A'  // death date
                ];
            }
        }
        
        // Add mortality records
        foreach ($mortalityPatients as $patient) {
            $rows[] = [
                'MORTALITY',
                $patient->id,
                $patient->first_name . ' ' . $patient->last_name,
                $patient->age,
                $patient->sex,
                'N/A', // disease name
                'N/A', // diagnosis date
                'N/A', // severity
                $patient->deathRecord?->cause_of_death ?? 'N/A',
                $patient->deathRecord?->date_of_death?->format('Y-m-d') ?? 'N/A'
            ];
        }
        
        return [
            'headers' => [
                'Type',
                'Resident ID',
                'Full Name',
                'Age',
                'Gender',
                'Disease/Condition',
                'Diagnosis Date',
                'Severity',
                'Cause of Death',
                'Death Date'
            ],
            'rows' => $rows
        ];
    }
}