<?php

namespace App\Actions\Notifications;

use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;

class MarkNotificationsRead
{
    use AsAction;

    public function asController(Request $request, ?string $id = null)
    {
        $user = $request->user();

        if ($id) {
            $user->notifications()->whereKey($id)->update(['read_at' => now()]);
        } else {
            $user->unreadNotifications->markAsRead();
        }

        return back();
    }
}
