<?php

namespace App\Jobs;

use App\Services\PatientImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportPatientsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $rows;

    /**
     * Create a new job instance.
     */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    /**
     * Execute the job.
     */
    public function handle(PatientImportService $importService): void
    {
        try {
            $results = $importService->importPatients($this->rows);
            
            Log::info('Patient import completed', [
                'success' => $results['success'],
                'failed' => $results['failed'],
                'errors' => $results['errors'],
            ]);
        } catch (\Exception $e) {
            Log::error('Patient import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }
}



