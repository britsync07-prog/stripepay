import { usePage } from '@inertiajs/react';

import { RealtimeNotifications } from '@/components/realtime-notifications';
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';

export default function AppLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    const { auth } = usePage().props;

    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs}>
            {auth?.user && <RealtimeNotifications userId={auth.user.id} />}
            {children}
        </AppLayoutTemplate>
    );
}
