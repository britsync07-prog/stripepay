<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class DashboardAccessNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_received',
            'title' => 'Payment received',
            'message' => 'Thank you for your payment — your consultation is being prepared.',
            'url' => route('portal.dashboard', [], false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {

        $url = URL::temporarySignedRoute(
            'portal.magic-login',
            now()->addHour(),
            ['user' => $notifiable->getKey()],
        );

        return (new MailMessage)
            ->subject('Access your '.config('app.name').' dashboard')
            ->greeting('Welcome'.($notifiable->name ? ', '.$notifiable->name : '').'!')
            ->line('Thank you for your payment. Click below to securely access your client dashboard and track your consultation — no password needed.')
            ->action('Access my dashboard', $url)
            ->line('This secure link expires in 1 hour. If it lapses, use “Forgot your password?” on the login page to get back in — your account is under this email address.')
            ->line('If you did not make this payment, you can safely ignore this email.');
    }
}
