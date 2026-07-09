import { Head } from '@inertiajs/react';

import { NotificationsList } from '@/components/notifications-list';
import { PageHeader } from '@/components/page-header';
import { Reveal } from '@/components/reveal';
import type { AppNotification } from '@/types';

export default function AdminNotifications({
    notifications,
}: {
    notifications: AppNotification[];
}) {
    const unread = notifications.filter((n) => !n.read).length;

    return (
        <>
            <Head title="Notifications" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Activity"
                    title="Notifications"
                    description={`${unread > 0 ? `${unread} unread · ` : ''}${notifications.length} total`}
                />

                <Reveal className="surface-card p-2">
                    <NotificationsList
                        items={notifications}
                        scroll={false}
                        title={null}
                    />
                </Reveal>
            </div>
        </>
    );
}
