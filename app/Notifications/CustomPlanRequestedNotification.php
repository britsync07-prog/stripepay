<?php

namespace App\Notifications;

use App\Models\ConsultationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerts admins that a client has requested a tailored plan. They review it,
 * set a price, and send back a payment link from the consultation page.
 */
class CustomPlanRequestedNotification extends Notification implements ShouldQueue
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
            'type' => 'custom_plan_requested',
            'title' => 'Custom plan requested',
            'message' => ($c->name ?: 'A client').' requested a tailored plan.',
            'consultation_uuid' => $c->uuid,
            'client_name' => $c->name,
            'client_email' => $c->email,
            'url' => route('admin.consultation', $c, false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $c = $this->consultation;

        return (new MailMessage)
            ->subject('New custom plan request')
            ->greeting('New custom plan request')
            ->line(($c->name ?: 'A client').' has requested a tailored plan.')
            ->line('Client: '.($c->name ?: '—').' ('.($c->email ?: '—').')')
            ->when($c->company, fn (MailMessage $m) => $m->line('Company: '.$c->company))
            ->line('What they need:')
            ->line($c->message ?: '—')
            ->action('Review & set a price', route('admin.consultation', $c));
    }
}
