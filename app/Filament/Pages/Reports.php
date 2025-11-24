<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\Report;
use App\Enums\RoleEnum;
use Filament\Pages\Page;
use Filament\Tables\Table;
use App\Models\Consultation;
use Filament\Actions\Action;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Support\HtmlString;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use App\Services\PDFGenerationService;
use Filament\Resources\Components\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Concerns\InteractsWithTable;
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
            RoleEnum::ADMIN,
            RoleEnum::MHO,
            RoleEnum::BHW,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_report')
                ->label('Generate Report')
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

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Consultation Reports')
            ->query(fn () => Consultation::with('program', 'patient')
                ->whereNotNull('program_id')
                ->where('program_id', '>', 0)
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
                    ->date('M d, Y')
                    ->sortable()
                    ->placeholder('No Date'),
            ])
            ->filters([
                SelectFilter::make('program_id')
                    ->label('Program')
                    ->relationship('program', 'name')
                    ->preload(),
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
}