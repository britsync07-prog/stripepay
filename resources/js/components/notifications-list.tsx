import { router, usePage } from '@inertiajs/react';
import { Bell, CheckCheck } from 'lucide-react';

import { NotificationItem } from '@/components/notification-item';
import { cn } from '@/lib/utils';
import { read, readAll } from '@/routes/notifications';
import type { AppNotification } from '@/types';

export function NotificationsList({
    items: explicitItems,
    scroll = true,
    title = 'Notifications',
}: {
    items?: AppNotification[];
    scroll?: boolean;
    title?: string | null;
}) {
    const feed = usePage().props.notifications;
    const items = explicitItems ?? feed?.items ?? [];
    const unreadCount = items.filter((i) => !i.read).length;

    const open = (n: AppNotification) => {
        router.post(
            read(n.id).url,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    if (!n.url) {
                        return;
                    }

                    const target = new URL(n.url, window.location.origin);
                    router.visit(target.pathname + target.search);
                },
            },
        );
    };

    const markAll = () =>
        router.post(readAll().url, {}, { preserveScroll: true });

    const showHeader = title !== null || unreadCount > 0;

    return (
        <div>
            {showHeader && (
                <div className="flex items-center justify-between px-1 pb-2">
                    <div className="flex items-center gap-2">
                        {title !== null && (
                            <span className="text-sm font-semibold">
                                {title}
                            </span>
                        )}
                        {unreadCount > 0 && (
                            <span className="rounded-full bg-primary/15 px-1.5 py-0.5 text-[10px] font-semibold text-primary">
                                {unreadCount} new
                            </span>
                        )}
                    </div>
                    {unreadCount > 0 && (
                        <button
                            onClick={markAll}
                            className="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground transition-colors hover:text-foreground"
                        >
                            <CheckCheck className="size-3.5" /> Mark all read
                        </button>
                    )}
                </div>
            )}

            {items.length === 0 ? (
                <div className="flex flex-col items-center justify-center gap-2 py-12 text-center">
                    <span className="flex size-11 items-center justify-center rounded-full bg-muted">
                        <Bell className="size-5 text-muted-foreground" />
                    </span>
                    <p className="text-sm text-muted-foreground">
                        You're all caught up.
                    </p>
                </div>
            ) : (
                <ul
                    className={cn(
                        'divide-y divide-border/60',
                        scroll && 'max-h-[26rem] overflow-y-auto',
                    )}
                >
                    {items.map((n) => (
                        <li key={n.id}>
                            <NotificationItem notification={n} onOpen={open} />
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
