<?php

namespace App\Notifications;

use App\Models\ConsultationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentFlaggedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ConsultationRequest $consultation,
        public string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    private function headline(): string
    {
        return match ($this->reason) {
            'disputed' => 'Payment disputed (chargeback)',
            'failed' => 'Payment failed',
            default => 'Payment refunded',
        };
    }

    public function toArray(object $notifiable): array
    {
        $c = $this->consultation;

        return [
            'type' => 'payment_'.$this->reason,
            'title' => $this->headline(),
            'message' => $this->headline().' — '.($c->name ?: 'a client').', '.$c->formattedAmount()
                .' for '.($c->plan?->name ?? 'a consultation').'. Review before paying the expert.',
            'consultation_uuid' => $c->uuid,
            'reference' => strtoupper(substr($c->uuid, 0, 8)),
            'amount' => $c->formattedAmount(),
            'client_name' => $c->name,
            'client_email' => $c->email,
            'url' => route('admin.consultation', $c, false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $c = $this->consultation;

        return (new MailMessage)
            ->error()
            ->subject($this->headline().' — '.strtoupper(substr($c->uuid, 0, 8)))
            ->greeting($this->headline())
            ->line('A consultation payment has been '.$this->reason.'.')
            ->line('Amount: '.$c->formattedAmount())
            ->line('Client: '.($c->name ?: '—').' ('.($c->email ?: '—').')')
            ->line('Reference: '.strtoupper(substr($c->uuid, 0, 8)))
            ->line('If an expert payout was already sent for this engagement, review whether it needs to be held or clawed back.')
            ->action('Review consultation', route('admin.consultation', $c));
    }
}
