<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $ipAddress,
        public string $userAgent,
        public string $loginTime
    ) {}

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
            ->subject('New Login to Your Account - CommHealth')
            ->greeting("Hello {$notifiable->name},")
            ->line('We wanted to let you know that your account was recently accessed.')
            ->line('**Login details:**')
            ->line("**Time:** {$this->loginTime}")
            ->line("**IP Address:** {$this->ipAddress}")
            ->line("**Device/Browser:** {$this->userAgent}")
            ->line('If this was you, you can safely ignore this email.')
            ->line('If you did not log in to your account, please change your password immediately and contact support.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'login_time' => $this->loginTime,
        ];
    }
}
