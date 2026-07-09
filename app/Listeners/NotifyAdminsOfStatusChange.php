<?php

namespace App\Listeners;

use App\Events\ConsultationStatusChanged;
use App\Models\User;
use App\Notifications\ConsultationStatusUpdatedNotification;
use Illuminate\Support\Facades\Notification;

class NotifyAdminsOfStatusChange
{
    public function handle(ConsultationStatusChanged $event): void
    {
        Notification::send(
            User::role('admin')->get(),
            new ConsultationStatusUpdatedNotification($event->consultation, $event->to),
        );
    }
}
