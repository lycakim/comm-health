<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Log;

class SetFreshLoginOnEmailVerification
{
    /**
     * Handle the event.
     */
    public function handle(Verified $event): void
    {
        // Set fresh_login flag so privacy modal shows after email verification
        session(['fresh_login' => true]);
        Log::info('Fresh login flag set after email verification for user: ' . $event->user->id);
    }
}
