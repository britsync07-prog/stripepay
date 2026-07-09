<?php

namespace App\Actions\Clients;

use App\Data\Notification\NotificationData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ShowClientNotifications
{
    use AsAction;

    public function asController(Request $request)
    {
        return Inertia::render('portal/notifications', [
            'notifications' => NotificationData::collect(
                $request->user()->notifications()->latest()->limit(50)->get()
            ),
        ]);
    }
}
