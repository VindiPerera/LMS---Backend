<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

/**
 * Custom password-reset email: the stock Illuminate\Auth\Notifications\
 * ResetPassword builds its link via route('password.reset', ...), which
 * doesn't exist in this API-only app (no web password-reset page for it to
 * point at). Instead we mail the raw token, which the Flutter app's Reset
 * Password screen asks the user to paste back in alongside a new password.
 */
class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $token)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset your FaceTalk password')
            ->line('You requested a password reset for your FaceTalk account.')
            ->line('Enter this code in the app to set a new password:')
            ->line(new HtmlString("<strong style=\"font-size:22px;letter-spacing:3px;\">{$this->token}</strong>"))
            ->line('This code expires in 60 minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}
