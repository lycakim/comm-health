<?php

namespace App\Jobs;

use App\Imports\PatientsImport;
use App\Notifications\ImportCompletedNotification;
use App\Services\ImportErrorSimplifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ImportPatientsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Run for up to 15 minutes (500+ rows). */
    public int $timeout = 900;

    /**
     * Create a new job instance.
     *
     * @param  array<string>  $storagePaths  Paths relative to storage (e.g. ["imports/file.xlsx"])
     * @param  int  $userId  User to run import as and to notify when done
     */
    public function __construct(
        protected array $storagePaths,
        protected int $userId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit($this->timeout);

        $user = \App\Models\User::find($this->userId);
        if (!$user) {
            return;
        }

        Auth::loginUsingId($this->userId);

        $totalSuccess = 0;
        $totalFailed = 0;
        $totalSkipped = 0;
        $allErrors = [];
        $extraErrorsCount = 0;
        $filesProcessed = 0;

        foreach ($this->storagePaths as $storagePath) {
            $filePath = $this->resolveFilePath($storagePath);
            if (!$filePath || !file_exists($filePath)) {
                $allErrors[] = ['file' => basename($storagePath), 'message' => 'File not found'];
                $totalFailed++;
                continue;
            }

            try {
                $import = new PatientsImport();
                $results = $import->import($filePath);
                $totalSuccess += $results['success'];
                $totalFailed += $results['failed'];
                $totalSkipped += $results['skipped'] ?? 0;
                $extraErrorsCount += $results['extra_errors_count'] ?? 0;
                if (!empty($results['errors'])) {
                    $fileErrors = array_map(fn ($err) => array_merge($err, ['file' => basename($storagePath)]), $results['errors']);
                    $allErrors = array_merge($allErrors, $fileErrors);
                }
                $filesProcessed++;
            } catch (\Throwable $e) {
                $totalFailed++;
                $allErrors[] = ['file' => basename($storagePath), 'message' => $e->getMessage()];
            }

            $this->deleteFileAfterImport($storagePath);
        }

        Auth::logout();

        $this->notifyUser($user, $filesProcessed, $totalSuccess, $totalFailed, $totalSkipped, $allErrors, $extraErrorsCount);
    }

    protected function resolveFilePath(string $storagePath): ?string
    {
        if (Storage::disk('public')->exists($storagePath)) {
            return Storage::disk('public')->path($storagePath);
        }
        if (Storage::disk('local')->exists($storagePath)) {
            return Storage::disk('local')->path($storagePath);
        }
        $candidates = [
            storage_path('app/public/' . $storagePath),
            storage_path('app/private/' . $storagePath),
            storage_path('app/' . $storagePath),
        ];
        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        return null;
    }

    protected function deleteFileAfterImport(string $storagePath): void
    {
        if (Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->delete($storagePath);
        } elseif (Storage::disk('local')->exists($storagePath)) {
            Storage::disk('local')->delete($storagePath);
        }
    }

    protected function notifyUser(
        \App\Models\User $user,
        int $filesProcessed,
        int $totalSuccess,
        int $totalFailed,
        int $totalSkipped,
        array $allErrors,
        int $extraErrorsCount
    ): void {
        $message = "Imported {$totalSuccess} resident(s) from {$filesProcessed} file(s).";
        if ($totalSkipped > 0) {
            $message .= " {$totalSkipped} skipped (already exist).";
        }
        if ($totalFailed > 0) {
            $message .= " {$totalFailed} failed.";
        }

        $displayLimit = 10;
        $errorDetails = collect($allErrors)->take($displayLimit)->map(function ($error) {
            $simplified = ImportErrorSimplifier::simplifyForDisplay($error);
            $file = $simplified['file'] ?? 'Unknown file';
            $row = isset($simplified['row']) ? "Row {$simplified['row']}" : '';
            $msg = $simplified['message'];
            return $row ? "{$file} - {$row}: {$msg}" : "{$file}: {$msg}";
        })->implode("\n");

        $summary = '';
        if ($totalFailed > $displayLimit) {
            $summary = "Showing first {$displayLimit} of {$totalFailed} errors:\n\n";
        }
        if ($extraErrorsCount > 0) {
            $summary .= "(Only first " . count($allErrors) . " errors stored.)\n\n";
        }
        $body = $message;
        if ($totalFailed > 0) {
            $body .= "\n\n" . $summary . ($errorDetails ?: "Fix the issues in your file and try again.");
        }

        $user->notify(new ImportCompletedNotification(
            'Import completed',
            $body,
            $totalFailed > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle',
            $totalFailed > 0 ? 'warning' : 'success',
        ));
    }
}
