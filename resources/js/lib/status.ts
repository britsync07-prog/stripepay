import type { ComponentProps } from 'react';

import type { Badge } from '@/components/ui/badge';

type BadgeVariant = NonNullable<ComponentProps<typeof Badge>['variant']>;

const STATUS_VARIANT: Record<string, BadgeVariant> = {
    paid: 'success',
    completed: 'success',
    resolved: 'success',
    closed: 'secondary',
    pending: 'warning',
    unpaid: 'warning',
    overdue: 'destructive',
    in_progress: 'info',
    open: 'info',
    draft: 'secondary',
    cancelled: 'destructive',
    succeeded: 'success',
    processing: 'info',
    requires_payment_method: 'warning',
    failed: 'destructive',
    denied: 'destructive',
    disputed: 'destructive',
    chargeback: 'destructive',
    refunded: 'secondary',
    expired: 'secondary',
};

export function statusBadgeVariant(status: string): BadgeVariant {
    return STATUS_VARIANT[status.toLowerCase()] ?? 'secondary';
}

export function humanizeStatus(status: string): string {
    return status.replace(/_/g, ' ');
}
