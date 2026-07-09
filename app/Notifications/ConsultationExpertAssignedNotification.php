<?php

namespace App\Notifications;

use App\Models\ConsultationRequest;
use App\Models\Expert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConsultationExpertAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ConsultationRequest $consultation,
        public ?Expert $expert,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $c = $this->consultation;

        return [
            'type' => 'consultation_assigned',
            'title' => 'Expert assigned',
            'message' => $this->expert
                ? "Consultation {$c->reference} assigned to {$this->expert->name}."
                : "Consultation {$c->reference} unassigned.",
            'consultation_uuid' => $c->uuid,
            'reference' => $c->reference,
            'url' => route('admin.consultation', $c, false),
        ];
    }
}
