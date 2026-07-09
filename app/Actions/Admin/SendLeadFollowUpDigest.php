<?php

namespace App\Actions\Admin;

use App\Models\ConsultationRequest;
use App\Models\User;
use App\Notifications\LeadFollowUpsDueNotification;
use Illuminate\Support\Facades\Notification;
use Lorisleiva\Actions\Concerns\AsAction;

class SendLeadFollowUpDigest
{
    use AsAction;

    public function handle(): array
    {
        $buckets = ConsultationRequest::openFollowUps()
            ->get()
            ->groupBy(fn (ConsultationRequest $c) => $c->followUpBucket());

        $overdue = $buckets->get('overdue')?->count() ?? 0;
        $dueToday = $buckets->get('today')?->count() ?? 0;

        if ($overdue + $dueToday === 0) {
            return ['overdue' => 0, 'dueToday' => 0, 'notified' => 0];
        }

        $admins = User::role('admin')->get();
        Notification::send($admins, new LeadFollowUpsDueNotification($dueToday, $overdue));

        return ['overdue' => $overdue, 'dueToday' => $dueToday, 'notified' => $admins->count()];
    }
}
