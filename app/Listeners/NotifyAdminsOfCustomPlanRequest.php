<?php

namespace App\Listeners;

use App\Events\CustomPlanRequested;
use App\Models\User;
use App\Notifications\CustomPlanRequestedNotification;
use Illuminate\Support\Facades\Notification;

class NotifyAdminsOfCustomPlanRequest
{
    public function handle(CustomPlanRequested $event): void
    {
        Notification::send(
            User::role('admin')->get(),
            new CustomPlanRequestedNotification($event->consultation),
        );
    }
}
