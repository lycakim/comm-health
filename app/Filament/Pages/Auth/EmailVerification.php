<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\EmailVerification\EmailVerificationPrompt as BaseEmailVerificationPrompt;
use Filament\Notifications\Notification;

class EmailVerification extends BaseEmailVerificationPrompt
{
    protected static string $view = 'filament.pages.auth.email-verification';

    public function mount(): void
    {
        parent::mount();
        
        // Automatically send verification email if user hasn't verified and we haven't sent one in this session
        if (auth()->check()) {
            $user = $this->getVerifiable();
            
            if (!$user->hasVerifiedEmail() && !session()->has('verification_email_sent')) {
                try {
                    $this->sendEmailVerificationNotification($user);
                    session(['verification_email_sent' => true]);
                    
                    Notification::make()
                        ->title('Verification email sent')
                        ->body('A verification link has been sent to your email address. Please check your inbox.')
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    // Silently fail - user can manually resend if needed
                }
            }
        }
    }

    public function resend(): void
    {
        try {
            $this->rateLimit(2);
        } catch (\DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/email-verification/email-verification-prompt.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(array_key_exists('body', __('filament-panels::pages/auth/email-verification/email-verification-prompt.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/email-verification/email-verification-prompt.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]) : null)
                ->danger()
                ->send();

            return;
        }

        $user = $this->getVerifiable();

        if ($user->hasVerifiedEmail()) {
            return;
        }

        $this->sendEmailVerificationNotification($user);
        
        // Update session flag so we know an email was sent
        session(['verification_email_sent' => true]);
        
        Notification::make()
            ->title('Verification email sent')
            ->body('A new verification link has been sent to your email address.')
            ->success()
            ->send();
    }
}
