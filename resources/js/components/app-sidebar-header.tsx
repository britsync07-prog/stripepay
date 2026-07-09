import { Link, usePage } from '@inertiajs/react';
import { AlarmClock, Bell } from 'lucide-react';

import { Breadcrumbs } from '@/components/breadcrumbs';
import { NotificationsList } from '@/components/notifications-list';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { followUps, notifications as adminNotifications } from '@/routes/admin';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const unread = usePage().props.notifications?.unread ?? 0;

    return (
        <header className="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            <div className="flex items-center gap-1">
                <Link
                    href={followUps()}
                    aria-label="Follow-ups"
                    title="Follow-ups"
                    className="flex size-9 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                >
                    <AlarmClock className="size-4" />
                </Link>

                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button
                            aria-label="Notifications"
                            className="relative flex size-9 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                        >
                            <Bell className="size-4" />
                            {unread > 0 && (
                                <span className="absolute top-1.5 right-1.5 flex h-3.5 min-w-3.5 items-center justify-center rounded-full bg-primary px-1 text-[9px] font-semibold text-primary-foreground">
                                    {unread}
                                </span>
                            )}
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        align="end"
                        className="w-96 max-w-[calc(100vw-2rem)] p-2"
                    >
                        <NotificationsList />
                        <Link
                            href={adminNotifications()}
                            className="mt-1 block rounded-md px-2 py-2 text-center text-xs font-medium text-primary transition-colors hover:bg-accent"
                        >
                            View all notifications
                        </Link>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>
    );
}
