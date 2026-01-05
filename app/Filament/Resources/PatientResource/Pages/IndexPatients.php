<?php

namespace App\Filament\Resources\PatientResource\Pages;

use App\Filament\Resources\PatientResource;
use App\Models\Patient;
use App\Exports\PatientTemplateExport;
use App\Imports\PatientsImport;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

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

        if (PatientResource::canCreate()) {
            $actions[] = Actions\Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function () {
                    return Excel::download(new PatientTemplateExport(), 'patient_import_template_' . date('Y-m-d') . '.xlsx');
                });

            $actions[] = Actions\Action::make('importPatients')
                ->label('Import Residents')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->form([
                    FileUpload::make('file')
                        ->label('Excel File')
                        ->disk('public')
                        ->directory('imports')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv'])
                        ->required()
                        ->helperText('Upload an Excel file (.xlsx, .xls) or CSV file with patient data. Download the template for the correct format.'),
                ])
                ->action(function (array $data) {
                    try {
                        // Get the full path to the uploaded file
                        // FileUpload stores files relative to the disk root
                        $filePath = null;
                        
                        // Try public disk first
                        if (Storage::disk('public')->exists($data['file'])) {
                            $filePath = Storage::disk('public')->path($data['file']);
                        }
                        // Try local disk
                        elseif (Storage::disk('local')->exists($data['file'])) {
                            $filePath = Storage::disk('local')->path($data['file']);
                        }
                        // Try default disk
                        elseif (Storage::exists($data['file'])) {
                            $filePath = Storage::path($data['file']);
                        }
                        // Try direct path
                        else {
                            $possiblePaths = [
                                storage_path('app/public/' . $data['file']),
                                storage_path('app/private/' . $data['file']),
                                storage_path('app/' . $data['file']),
                            ];
                            
                            foreach ($possiblePaths as $path) {
                                if (file_exists($path)) {
                                    $filePath = $path;
                                    break;
                                }
                            }
                        }
                        
                        if (!$filePath || !file_exists($filePath)) {
                            throw new \Exception('Uploaded file not found. Please try uploading again. File: ' . ($data['file'] ?? 'unknown'));
                        }
                        
                        $import = new PatientsImport();
                        Excel::import($import, $filePath);
                        
                        // Clean up the uploaded file after import
                        if (Storage::disk('public')->exists($data['file'])) {
                            Storage::disk('public')->delete($data['file']);
                        } elseif (Storage::disk('local')->exists($data['file'])) {
                            Storage::disk('local')->delete($data['file']);
                        }
                        
                        $results = $import->getResults();
                        
                        $message = "Successfully imported {$results['success']} resident(s).";
                        if ($results['failed'] > 0) {
                            $message .= " {$results['failed']} failed.";
                        }
                        
                        Notification::make()
                            ->title('Import Completed')
                            ->body($message)
                            ->success()
                            ->send();
                        
                        // Show errors if any
                        if (!empty($results['errors'])) {
                            $errorDetails = collect($results['errors'])->take(5)->map(function ($error) {
                                return "Row {$error['row']}: {$error['message']}";
                            })->implode("\n");
                            
                            Notification::make()
                                ->title('Import Errors')
                                ->body($errorDetails . (count($results['errors']) > 5 ? "\n... and " . (count($results['errors']) - 5) . " more" : ''))
                                ->warning()
                                ->persistent()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
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