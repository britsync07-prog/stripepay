<?php

namespace App\Notifications;

use App\Enums\ConsultationWorkStatus;
use App\Models\ConsultationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConsultationStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ConsultationRequest $consultation,
        public ConsultationWorkStatus $status,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $c = $this->consultation;

        return [
            'type' => 'consultation_status',
            'title' => 'Consultation update',
            'message' => "Consultation {$c->reference} is now {$this->status->label()}.",
            'consultation_uuid' => $c->uuid,
            'reference' => $c->reference,
            'status' => $this->status->value,
            'url' => route('admin.consultation', $c, false),
        ];
    }
}
