<?php

namespace App\Filament\Resources\PatientResource\Pages;

use Filament\Actions;
use App\Models\Barangay;
use App\Exports\PatientTemplateExport;
use App\Jobs\ImportPatientsJob;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\Resources\PatientResource;
use App\Enums\RoleEnum;
use Filament\Tables\Table;

class ListPatients extends ListRecords
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];
        
        // Only show button if user is not MHO AND has assigned barangay_id
        if (!Auth::user()->isMHO() && !is_null(Auth::user()->barangay_id)) {
            $actions[] = Actions\CreateAction::make()
                ->icon('heroicon-o-plus');
        }
        
        // Add import actions only for BHW and Midwife
        if (Auth::user()->isBHW() || Auth::user()->isMidwife()) {
            $actions[] = Actions\Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function () {
                    return PatientTemplateExport::download('resident_import_template_' . date('Y-m-d') . '.xlsx');
                });

            $actions[] = Actions\Action::make('importPatients')
                ->label('Import Residents')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->form([
                    FileUpload::make('files')
                        ->label('Excel / CSV Files')
                        ->disk('public')
                        ->directory('imports')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv'])
                        ->multiple()
                        ->required()
                        ->helperText('Upload Excel (.xlsx) or CSV files. All data in the file will be imported. Download the template for the expected columns.'),
                ])
                ->action(function (array $data) {
                    $files = is_array($data['files']) ? $data['files'] : [$data['files']];
                    $storagePaths = [];
                    foreach ($files as $file) {
                        if (Storage::disk('public')->exists($file) || Storage::disk('local')->exists($file)) {
                            $storagePaths[] = $file;
                        } else {
                            Notification::make()
                                ->title('Import not started')
                                ->body('One or more files could not be found. Please upload again.')
                                ->danger()
                                ->send();
                            return;
                        }
                    }
                    if (empty($storagePaths)) {
                        Notification::make()
                            ->title('Import not started')
                            ->body('No valid files to import.')
                            ->danger()
                            ->send();
                        return;
                    }

                    ImportPatientsJob::dispatch($storagePaths, Auth::id());

                    Notification::make()
                        ->title('Import started')
                        ->body('Your file(s) are being imported in the background. You will be notified when the import is finished.')
                        ->success()
                        ->send();
                })
                ->modalHeading('Bulk Import Residents')
                ->modalDescription('Upload an Excel or CSV file to import multiple residents at once.')
                ->modalSubmitActionLabel('Import')
                ->modalWidth('xl');
        }
        
        return $actions;
    }

    // IMPORTANT: Override this method to control the default create action
    protected static bool $shouldRegisterNavigation = true;

    /**
     * Get barangay ID from Filament table filter in URL
     */
    protected function getBarangayFromTableFilter(): ?int
    {
        // Get the table filters from the URL
        $tableFilters = request()->query('tableFilters', []);
        
        // Check if barangay_id filter is set
        if (isset($tableFilters['barangay_id']['value'])) {
            $barangayId = $tableFilters['barangay_id']['value'];
            return is_numeric($barangayId) ? (int) $barangayId : null;
        }
        
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        $barangayId = $this->getBarangayFromTableFilter();

        if ($barangayId) {
            $barangay = Barangay::find($barangayId);

            if (!$barangay) {
                return 'View and manage resident records across all barangays';
            }
            
            return 'View and manage resident records in ' . $barangay->name;
        }
        
        return 'View and manage resident records across all barangays';
    }
}