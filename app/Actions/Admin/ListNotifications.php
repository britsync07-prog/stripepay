<?php

namespace App\Actions\Admin;

use App\Data\Notification\NotificationData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ListNotifications
{
    use AsAction;

    public function asController(Request $request)
    {
        return Inertia::render('admin/notifications', [
            'notifications' => NotificationData::collect(
                $request->user()->notifications()->latest()->limit(50)->get()
            ),
        ]);
    }
}
