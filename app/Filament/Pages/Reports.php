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
use App\Services\PDFGenerationService;
use Filament\Resources\Components\Tab;
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

class Reports extends Page implements HasTable
{
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
                        $mimeType = $record->format === 'pdf' ? 'application/pdf' : 'text/csv';
                        
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
                    Auth::user()->role !== RoleEnum::MHO->value,
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
                            ])
                            ->required()
                            ->default('resident-profiling'),
                        Select::make('format')
                            ->label('Export Format')
                            ->options([
                                'csv' => 'CSV (Spreadsheet)',
                                'pdf' => 'PDF (Document)',
                            ])
                            ->default('csv')
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
     * Preview a report without generating/downloading
     */
    public function previewReport(string $reportType)
    {
        try {
            // Normalize report type (handle both patient-profiling and resident-profiling)
            $normalizedType = $this->normalizeReportType($reportType);
            
            // Get data based on report type
            $data = $this->getReportData($normalizedType);
            
            // Check if there's no data
            if (empty($data['rows']) || count($data['rows']) === 0) {
                Notification::make()
                    ->title('No Data Available')
                    ->body('There is no data available for this report. Please try a different report type or check back later.')
                    ->warning()
                    ->send();
                return;
            }
            
            // Generate PDF for preview
            $pdfService = app(PDFGenerationService::class);
            $pdf = $pdfService->generateReportDataPdf($data, $normalizedType);
            
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
            
            // Check if there's no data
            if (empty($data['rows']) || count($data['rows']) === 0) {
                Notification::make()
                    ->title('No Data Available')
                    ->body('There is no data available for this report. Please try a different report type or check back later.')
                    ->warning()
                    ->send();
                return;
            }
            
            $pdfService = app(PDFGenerationService::class);
            $user = Auth::user();
            $filename = "{$normalizedType}_" . now()->format('Y-m-d_His') . ".{$format}";
            $fileContent = null;
            
            // Handle PDF export
            if ($format === 'pdf') {
                $pdf = $pdfService->generateReportDataPdf($data, $normalizedType);
                $fileContent = $pdf->output();
            } else {
                // Handle CSV export
                $handle = fopen('php://temp', 'r+');
                
                // Add UTF-8 BOM for Excel compatibility
                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // Add headers
                fputcsv($handle, $data['headers']);
                
                // Add rows
                foreach ($data['rows'] as $row) {
                    fputcsv($handle, $row);
                }
                
                rewind($handle);
                $fileContent = stream_get_contents($handle);
                fclose($handle);
            }
            
            // Save to storage
            $fileMetadata = $pdfService->saveReportToStorage($fileContent, $normalizedType, $format, $filename);
            
            // Get report title
            $reportTitles = [
                'patient-profiling' => 'Patient Profiling Report',
                'resident-profiling' => 'Patient Profiling Report',
                'maternal-child' => 'Maternal and Child Report',
                'senior-citizens' => 'Senior Citizens Health Status Report',
                'family-planning' => 'Family Planning Usage Report',
                'morbidity-mortality' => 'Morbidity and Mortality Report',
            ];
            
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
            } else {
                return Response::streamDownload(
                    fn () => print($fileContent),
                    $filename,
                    [
                        'Content-Type' => 'text/csv',
                        'Content-Disposition' => "attachment; filename={$filename}",
                    ]
                );
            }
            
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
                    $patient->date_of_birth?->format('Y-m-d'),
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