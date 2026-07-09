<?php

namespace App\Notifications;

use App\Models\ConsultationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewConsultationPaidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ConsultationRequest $consultation) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $c = $this->consultation;

        return [
            'type' => 'consultation_paid',
            'title' => 'New paid consultation',
            'message' => ($c->name ?: 'A client').' paid '.$c->formattedAmount().' for '.($c->plan?->name ?? 'a consultation').'.',
            'consultation_uuid' => $c->uuid,
            'reference' => strtoupper(substr($c->uuid, 0, 8)),
            'plan' => $c->plan?->name,
            'amount' => $c->formattedAmount(),
            'client_name' => $c->name,
            'client_email' => $c->email,
            'url' => route('admin.consultation', $c, false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $c = $this->consultation;
        $plan = $c->plan?->name ?? 'Consultation';

        return (new MailMessage)
            ->subject("New paid consultation — {$plan}")
            ->greeting('New consultation paid 🎉')
            ->line(($c->name ?: 'A client').' just paid for '.$plan.'.')
            ->line('Amount: '.$c->formattedAmount())
            ->line('Client: '.($c->name ?: '—').' ('.($c->email ?: '—').')')
            ->line('Reference: '.strtoupper(substr($c->uuid, 0, 8)))
            ->action('View consultation', route('admin.consultation', $c));
    }
}
