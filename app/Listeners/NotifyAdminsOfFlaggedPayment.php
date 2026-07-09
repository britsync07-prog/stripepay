<?php

namespace App\Listeners;

use App\Events\PaymentFlagged;
use App\Models\User;
use App\Notifications\PaymentFlaggedNotification;
use Illuminate\Support\Facades\Notification;

class NotifyAdminsOfFlaggedPayment
{
    public function handle(PaymentFlagged $event): void
    {
        Notification::send(
            User::role('admin')->get(),
            new PaymentFlaggedNotification($event->consultation, $event->reason),
        );
    }
}
