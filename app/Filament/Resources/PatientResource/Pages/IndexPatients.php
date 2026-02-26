<?php

namespace App\Filament\Resources\PatientResource\Pages;

use App\Filament\Resources\PatientResource;
use App\Jobs\ImportPatientsJob;
use App\Models\Patient;
use App\Exports\PatientTemplateExport;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class IndexPatients extends ListRecords
{
    protected static string $resource = PatientResource::class;

    public function mount(): void
    {
        parent::mount();
        
        if (Auth::user()->isMHO() || Auth::user()->isAdmin()) {
            redirect()->to(PatientResource::getUrl('all'));
        }
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];

        if (Auth::user()->isBHW() || Auth::user()->isMidwife()) {
            $actions[] = Actions\Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function () {
                    return PatientTemplateExport::download('patient_import_template_' . date('Y-m-d') . '.xlsx');
                });

            $actions[] = Actions\Action::make('importPatients')
                ->label('Import Residents')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->form([
                    FileUpload::make('files')
                        ->label('Excel/CSV Files')
                        ->disk('public')
                        ->directory('imports')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv'])
                        ->multiple()
                        ->required()
                        ->helperText('Upload one or more Excel files (.xlsx, .xls) or CSV files with patient data. Download the template for the correct format.'),
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

    // protected function getTableQuery(): ?Builder
    // {
    //     // For newer Filament versions, use this method instead of getTableQuery()
    //     return static::getResource()::getEloquentQuery()->where('barangay_id', Auth::user()->barangay_id);
    // }
}