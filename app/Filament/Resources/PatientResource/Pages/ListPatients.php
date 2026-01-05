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