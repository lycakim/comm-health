<?php

namespace App\Console\Commands;

use App\Imports\PatientsImport;
use App\Jobs\ImportPatientsJob;
use App\Notifications\ImportCompletedNotification;
use App\Services\ImportErrorSimplifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Process patient import files from a scheduled folder (e.g. 5k rows).
 *
 * Usage:
 *   php artisan patients:import-scheduled --user=2
 *   php artisan patients:import-scheduled --user=2 --sync   # Run import in this process (no queue worker needed)
 *
 * With --sync: runs the import in this process and sends notification when done. No queue:work needed.
 * Without --sync: moves files and dispatches ImportPatientsJob (requires queue:work to be running).
 *
 * To run every 5 minutes (with --sync so no queue needed):
 *   In routes/console.php: Schedule::command('patients:import-scheduled', ['--user' => 2, '--sync'])->everyFiveMinutes();
 *   Then: php artisan schedule:work
 */
class ProcessScheduledPatientImports extends Command
{
    protected $signature = 'patients:import-scheduled
                            {--user= : User ID to run as and to notify (required)}
                            {--sync : Run import in this process instead of dispatching a job (no queue worker needed)}';

    protected $description = 'Process CSV/XLSX files from storage/app/imports/scheduled and import or queue them';

    public function handle(): int
    {
        $userId = $this->option('user');
        if ($userId === null || (string) $userId === '') {
            $this->error('Option --user is required. Example: --user=2');
            return self::FAILURE;
        }

        $userId = (int) $userId;
        $user = \App\Models\User::find($userId);
        if (!$user) {
            $this->error("User #{$userId} not found.");
            return self::FAILURE;
        }

        $scheduledDir = 'imports/scheduled';
        $targetDir = 'imports';

        if (!Storage::disk('local')->exists($scheduledDir)) {
            Storage::disk('local')->makeDirectory($scheduledDir);
            $this->comment("Created {$scheduledDir}. Place CSV/XLSX files there.");
            return self::SUCCESS;
        }

        $files = Storage::disk('local')->files($scheduledDir);
        $allowed = array_filter($files, fn (string $f) => preg_match('/\.(csv|xlsx)$/i', $f));
        if (empty($allowed)) {
            return self::SUCCESS;
        }

        $moved = [];
        foreach ($allowed as $path) {
            $basename = basename($path);
            $targetPath = $targetDir . '/' . $basename;
            $fullContent = Storage::disk('local')->get($path);
            Storage::disk('public')->put($targetPath, $fullContent);
            Storage::disk('local')->delete($path);
            $moved[] = $targetPath;
        }

        if ($this->option('sync')) {
            $this->runImportSync($moved, $user);
        } else {
            ImportPatientsJob::dispatch($moved, $userId);
            $this->info('Dispatched import job for ' . count($moved) . ' file(s). User #' . $userId . ' will be notified when done.');
        }

        return self::SUCCESS;
    }

    protected function runImportSync(array $storagePaths, \App\Models\User $user): void
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(900);

        Auth::loginUsingId($user->id);

        $totalSuccess = 0;
        $totalFailed = 0;
        $totalSkipped = 0;
        $allErrors = [];
        $extraErrorsCount = 0;
        $filesProcessed = 0;

        foreach ($storagePaths as $storagePath) {
            $filePath = null;
            if (Storage::disk('public')->exists($storagePath)) {
                $filePath = Storage::disk('public')->path($storagePath);
            } elseif (Storage::disk('local')->exists($storagePath)) {
                $filePath = Storage::disk('local')->path($storagePath);
            }
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

            if (Storage::disk('public')->exists($storagePath)) {
                Storage::disk('public')->delete($storagePath);
            } elseif (Storage::disk('local')->exists($storagePath)) {
                Storage::disk('local')->delete($storagePath);
            }
        }

        Auth::logout();

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
            'Import completed (scheduled)',
            $body,
            $totalFailed > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle',
            $totalFailed > 0 ? 'warning' : 'success',
        ));

        $this->info($message);
    }
}
