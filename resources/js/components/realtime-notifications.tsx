import { router } from '@inertiajs/react';
import { useEchoNotification } from '@laravel/echo-react';

/**
 * Subscribes to the authenticated user's private channel and, whenever a
 * notification is broadcast, refreshes the current page's props — so the bell
 * feed AND any page data (e.g. the admin dashboard metrics) update live without
 * a manual reload. State and scroll are preserved. Renders nothing.
 */
export function RealtimeNotifications({ userId }: { userId: number }) {
    useEchoNotification(`App.Models.User.${userId}`, () => {
        router.reload();
    });

    return null;
}
