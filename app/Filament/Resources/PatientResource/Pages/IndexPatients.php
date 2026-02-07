<?php

namespace App\Filament\Resources\PatientResource\Pages;

use App\Filament\Resources\PatientResource;
use App\Models\Patient;
use App\Exports\PatientTemplateExport;
use App\Services\ImportErrorSimplifier;
use App\Imports\PatientsImport;
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
                    return PatientTemplateExport::download('patient_import_template_' . date('Y-m-d') . '.csv');
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
                    try {
                        $files = is_array($data['files']) ? $data['files'] : [$data['files']];
                        $totalSuccess = 0;
                        $totalFailed = 0;
                        $allErrors = [];
                        
                        foreach ($files as $file) {
                            // Get the full path to the uploaded file
                            $filePath = null;
                            
                            // Try public disk first
                            if (Storage::disk('public')->exists($file)) {
                                $filePath = Storage::disk('public')->path($file);
                            }
                            // Try local disk
                            elseif (Storage::disk('local')->exists($file)) {
                                $filePath = Storage::disk('local')->path($file);
                            }
                            // Try default disk
                            elseif (Storage::exists($file)) {
                                $filePath = Storage::path($file);
                            }
                            // Try direct path
                            else {
                                $possiblePaths = [
                                    storage_path('app/public/' . $file),
                                    storage_path('app/private/' . $file),
                                    storage_path('app/' . $file),
                                ];
                                
                                foreach ($possiblePaths as $path) {
                                    if (file_exists($path)) {
                                        $filePath = $path;
                                        break;
                                    }
                                }
                            }
                            
                            if (!$filePath || !file_exists($filePath)) {
                                $allErrors[] = ['file' => $file, 'message' => 'File not found'];
                                $totalFailed++;
                                continue;
                            }
                            
                            try {
                                $import = new PatientsImport();
                                $import->import($filePath);
                                
                                $results = $import->getResults();
                                $totalSuccess += $results['success'];
                                $totalFailed += $results['failed'];
                                
                                if (!empty($results['errors'])) {
                                    $allErrors = array_merge($allErrors, $results['errors']);
                                }
                            } catch (\Exception $e) {
                                $totalFailed++;
                                $allErrors[] = ['file' => basename($filePath), 'message' => $e->getMessage()];
                            }
                            
                            // Clean up the uploaded file after import
                            if (Storage::disk('public')->exists($file)) {
                                Storage::disk('public')->delete($file);
                            } elseif (Storage::disk('local')->exists($file)) {
                                Storage::disk('local')->delete($file);
                            }
                        }
                        
                        $message = "Successfully imported {$totalSuccess} resident(s) from " . count($files) . " file(s).";
                        if ($totalFailed > 0) {
                            $message .= " {$totalFailed} failed.";
                        }
                        
                        Notification::make()
                            ->title('Import Completed')
                            ->body($message)
                            ->success()
                            ->send();
                        
                        // Show errors if any (simplified for elderly users)
                        if (!empty($allErrors)) {
                            $errorDetails = collect($allErrors)->take(10)->map(function ($error) {
                                $simplified = ImportErrorSimplifier::simplifyForDisplay($error);
                                $file = $simplified['file'] ?? 'Unknown file';
                                $row = isset($simplified['row']) ? "Row {$simplified['row']}" : '';
                                $message = $simplified['message'];
                                return $row ? "{$file} - {$row}: {$message}" : "{$file}: {$message}";
                            })->implode("\n");
                            
                            Notification::make()
                                ->title('Import Errors')
                                ->body($errorDetails . (count($allErrors) > 10 ? "\n... and " . (count($allErrors) - 10) . " more" : ''))
                                ->warning()
                                ->persistent()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body(ImportErrorSimplifier::simplify($e->getMessage()))
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