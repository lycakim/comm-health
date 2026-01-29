<?php

namespace App\Filament\Resources\PatientResource\Pages;

use Filament\Actions;
use App\Models\Barangay;
use App\Exports\PatientTemplateExport;
use App\Imports\PatientsImport;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\Resources\PatientResource;
use Maatwebsite\Excel\Facades\Excel;

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
        
        // Add import actions if user can create
        if (PatientResource::canCreate()) {
            $actions[] = Actions\Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function () {
                    return Excel::download(new PatientTemplateExport(), 'resident_import_template_' . date('Y-m-d') . '.xlsx');
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
                                Excel::import($import, $filePath);
                                
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
                        
                        // Show errors if any
                        if (!empty($allErrors)) {
                            $errorDetails = collect($allErrors)->take(10)->map(function ($error) {
                                $file = $error['file'] ?? 'Unknown file';
                                $row = isset($error['row']) ? "Row {$error['row']}" : '';
                                $message = $error['message'] ?? 'Unknown error';
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

    // IMPORTANT: Override this method to control the default create action
    protected static bool $shouldRegisterNavigation = true;

    public function getSubheading(): string|Htmlable|null
    {
        $barangayFromRoute = request()->route('barangay');

        if ($barangayFromRoute) {
            $barangay = Barangay::where('id', $barangayFromRoute)->first();

            if (!$barangay) {
                return 'View and manage resident records across all barangays';
            }
            
            return 'View and manage resident records across barangay ' . $barangay->name;
        }
        
        return 'View and manage resident records across all barangays';
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        
        $barangayFromRoute = request()->route('barangay');
        
        if ($barangayFromRoute === 'all' || is_null($barangayFromRoute)) {
            return $query;
        }

        return $query->where('barangay_id', $barangayFromRoute);
    }
}