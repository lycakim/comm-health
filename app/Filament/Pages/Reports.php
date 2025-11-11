<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\Report;
use App\Enums\RoleEnum;
use App\Models\Location;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Resources\Components\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use App\Forms\Components\LocationSelect;
use App\Services\PDFGenerationService;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Concerns\InteractsWithTable;

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
                    
                    LocationSelect::make(), // Province → City/Municipality → Brgy → Purok
                    
                ])
                ->action(function (array $data, PDFGenerationService $pdfService) {
                    try {
                        $reportType = $data['report_type'];
                        $barangayId = $data['barangay_id'];
                        $purokId = $data['purok_id'];

                        // Generate the PDF
                        $pdf = $pdfService->generateReport($reportType, $barangayId, $purokId);
                        
                        // Get the filename
                        $filename = $pdfService->getReportFilename($reportType);
                        
                        // Log the report generation
                        $pdfService->logReportGeneration($reportType, $barangayId, true);
                        
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
                        if (isset($pdfService, $reportType, $barangayId)) {
                            $pdfService->logReportGeneration($reportType, $barangayId, false);
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
            ->heading('Generated Reports')
            ->query(fn () => Report::latest())
            ->columns([
                TextColumn::make('title')->searchable()
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ]);
    }

    // initialize auth user
    public static function currentUser(): ?User
    {
        return Auth::user();
    }
}