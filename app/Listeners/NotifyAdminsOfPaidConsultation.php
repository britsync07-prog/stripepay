<?php

namespace App\Listeners;

use App\Events\ConsultationPaid;
use App\Models\User;
use App\Notifications\NewConsultationPaidNotification;
use Illuminate\Support\Facades\Notification;

class NotifyAdminsOfPaidConsultation
{
    public function handle(ConsultationPaid $event): void
    {
        Notification::send(
            User::role('admin')->get(),
            new NewConsultationPaidNotification($event->consultation),
        );
    }
}
