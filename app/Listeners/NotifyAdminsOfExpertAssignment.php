<?php

namespace App\Listeners;

use App\Events\ExpertAssigned;
use App\Models\User;
use App\Notifications\ConsultationExpertAssignedNotification;
use Illuminate\Support\Facades\Notification;

class NotifyAdminsOfExpertAssignment
{
    public function handle(ExpertAssigned $event): void
    {
        Notification::send(
            User::role('admin')->get(),
            new ConsultationExpertAssignedNotification($event->consultation, $event->expert),
        );
    }
}
