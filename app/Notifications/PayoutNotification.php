<?php

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutNotification extends Notification
{
    use Queueable;

    public function __construct(public Payout $payout) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $p = $this->payout;
        $name = $p->expert?->name;

        $mail = (new MailMessage)
            ->subject('You have received a payout')
            ->greeting('Hello'.($name ? ", {$name}" : '').'!')
            ->line("A payout of {$p->formattedAmount()} has been sent to you via {$p->method->label()}.");

        if ($p->reference) {
            $mail->line("Reference: {$p->reference}");
        }

        return $mail->line('Thank you for your work with '.config('app.name').'.');
    }
}
