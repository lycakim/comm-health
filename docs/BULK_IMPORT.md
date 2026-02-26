# Bulk patient import (500+ rows, 5k+ rows)

## Why PHP/Laravel (no Python)

- **One stack**: Same app handles auth, validation, and DB. No separate Python service to deploy or secure.
- **Auth & permissions**: Import runs as the logged-in user (barangay scope, duplicate checks). A Python script would need API tokens or DB credentials and would duplicate logic.
- **Efficiency**: Chunked CSV streaming, chunked XLSX processing, and a queued job with higher memory/time limits handle 5k rows in this stack. Python would only help if the bottleneck were raw parsing speed; here the limit is usually memory and time, which we address with chunks and the queue.

If you hit limits (e.g. 10k+ rows), options are: run the **scheduled command with --sync** in a separate process with a higher PHP memory limit, or split files and import in batches.

---

## 1. Import from the UI (queue job)

1. Go to Residents and click **Import Residents**.
2. Upload CSV/XLSX file(s). The request returns immediately with “Import started”.
3. A **queue job** runs in the background and sends a **notification** when done.

**You must run a queue worker** so the job runs:

```bash
# Run forever (recommended for development). Stays running and processes jobs as they appear.
php artisan queue:work

# Do NOT use --once for normal use (it exits after one job, so the “Import completed” notification may be queued but never run).
```

**Why the notification didn’t appear before**

- The “Import completed” notification is now sent **synchronously** from the job (same process). You don’t need a second job to send it, so it appears as soon as the import job finishes.
- Laravel’s database channel was storing the payload from `toArray()`. We fixed `DatabaseNotification::toArray()` so the title/body/icon are stored and show in the UI.

**Why the worker seemed to “stop”**

- If you used `php artisan queue:work --once`, the worker exits after one job. Use `php artisan queue:work` (no `--once`) so it keeps running.
- If the job throws an unhandled exception, the worker can exit. Check `storage/logs/laravel.log`. With chunked processing and the sync notification, the job should complete without exhausting memory or time.

**Production**

- Run the worker under a process manager (e.g. **Supervisor**) so it restarts if it crashes and keeps running.

---

## 2. Scheduled command (no queue worker needed)

For 5k rows you can avoid the queue entirely and run the import on a schedule.

1. Put CSV/XLSX files in **`storage/app/imports/scheduled/`** (create the folder if needed).
2. Run:

```bash
php artisan patients:import-scheduled --user=2 --sync
```

Replace `2` with the user ID that should be notified when the import finishes. That user will get a database notification (bell icon) when done.

3. **Optional – run every 5 minutes**: In `routes/console.php`, uncomment and edit:

```php
Schedule::command('patients:import-scheduled', ['--user' => 2, '--sync'])->everyFiveMinutes();
```

Then run the scheduler in the background:

```bash
php artisan schedule:work
```

No `queue:work` is required. The import runs in the schedule process (with higher memory/time limits in the command).

---

## Summary

| Method              | When to use              | Needs queue:work? |
|---------------------|--------------------------|-------------------|
| UI + Import job     | User uploads from panel  | Yes               |
| Scheduled + --sync  | Files in a folder / cron | No                |

- **Notification**: Fixed so it appears in the UI and is sent in the same process as the import job.
- **Worker**: Use `php artisan queue:work` (no `--once`) so it keeps running and processes the import job and any future jobs.
