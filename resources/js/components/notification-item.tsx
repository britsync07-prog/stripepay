import { notificationMeta } from '@/constants/notifications';
import { cn } from '@/lib/utils';
import type { AppNotification } from '@/types';

export function NotificationItem({
    notification,
    onOpen,
}: {
    notification: AppNotification;
    onOpen: (notification: AppNotification) => void;
}) {
    const n = notification;
    const { icon: Icon, chip } = notificationMeta(n.type);

    return (
        <button
            onClick={() => onOpen(n)}
            className={cn(
                'group flex w-full items-start gap-3 rounded-xl px-3 py-3 text-left transition-colors hover:bg-muted/60',
                !n.read && 'bg-primary/[0.04]',
            )}
        >
            <span
                className={cn(
                    'mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full [&>svg]:size-4',
                    chip,
                    n.read && 'opacity-60',
                )}
            >
                <Icon />
            </span>

            <span className="min-w-0 flex-1">
                <span className="flex items-center gap-2">
                    <span
                        className={cn(
                            'truncate text-sm',
                            n.read ? 'font-medium' : 'font-semibold',
                        )}
                    >
                        {n.title}
                    </span>
                    {!n.read && (
                        <span className="size-1.5 shrink-0 rounded-full bg-primary" />
                    )}
                </span>
                <span className="mt-0.5 line-clamp-2 block text-xs text-muted-foreground">
                    {n.message}
                </span>
                {n.time && (
                    <span className="mt-1 block font-mono text-[11px] text-muted-foreground/60">
                        {n.time}
                    </span>
                )}
            </span>
        </button>
    );
}
