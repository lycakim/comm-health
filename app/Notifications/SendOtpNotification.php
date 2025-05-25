<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendOtpNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public string $otp, public int $expiresIn = 120)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
         return (new MailMessage)
            ->subject('Your One-Time Password (OTP) - CommHealth')
            ->greeting("Dear {$notifiable->name},")
            ->line("We received a request to log in to your account.")
            ->line("Please use the one-time password (OTP) below to proceed:")
            ->line('')
            ->line("**{$this->otp}**")
            ->line('')
            ->line("This code will expire in {$this->expiresIn} seconds.")
            ->line("If you did not request this OTP, please disregard this email.");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}