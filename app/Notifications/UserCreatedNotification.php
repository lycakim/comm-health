<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class UserCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected User $user)
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Use APP_URL from .env, or fallback to the app URL helper
        $loginUrl = rtrim(config('app.url'), '/') . '/commhealth/login';

        return (new MailMessage)
            ->subject('Welcome to ' . config('app.name'))
            ->greeting('Hello ' . $this->user->name . ',')
            ->line('Your account has been successfully created in our system.')
            ->line('You can now log in using your registered email address:')
            ->line('**Email:** ' . $this->user->email)
            ->line('Please use the password setup link or reset your password if you haven’t set one yet.')
            ->action('Login to Your Account', $loginUrl)
            ->line('If you did not request this account, please contact our support team.')
            ->salutation('Best regards, ' . config('app.name') . ' Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'notification_type' => 'user_created',
        ];
    }
}