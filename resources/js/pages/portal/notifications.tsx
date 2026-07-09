import { Head } from '@inertiajs/react';

import { NotificationsList } from '@/components/notifications-list';
import { PageHeader } from '@/components/page-header';
import { Panel } from '@/components/panel';
import type { AppNotification } from '@/types';

export default function ClientNotifications({
    notifications,
}: {
    notifications: AppNotification[];
}) {
    const unread = notifications.filter((n) => !n.read).length;

    return (
        <>
            <Head title="Notifications" />
            <div className="animate-fade-up space-y-6">
                <PageHeader
                    eyebrow="Portal"
                    title="Notifications"
                    description={`Updates about your consultations${unread > 0 ? ` · ${unread} unread` : ''}.`}
                />

                <Panel flush bodyClassName="p-2">
                    <NotificationsList
                        items={notifications}
                        scroll={false}
                        title={null}
                    />
                </Panel>
            </div>
        </>
    );
}
