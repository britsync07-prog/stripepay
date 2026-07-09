import type { ReactNode } from 'react';

import { Panel } from '@/components/panel';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export function Section({
    title,
    badge,
    accent,
    flush,
    children,
}: {
    title?: string;
    badge?: ReactNode;
    accent?: boolean;
    flush?: boolean;
    children: ReactNode;
}) {
    return (
        <Panel
            title={title}
            action={badge}
            flush={flush}
            className={cn('min-w-0', accent && 'border-primary/30')}
        >
            {children}
        </Panel>
    );
}

export function Detail({
    label,
    value,
}: {
    label: string;
    value: string | null;
}) {
    return (
        <div>
            <dt className="text-xs tracking-wide text-muted-foreground uppercase">
                {label}
            </dt>
            <dd className="mt-0.5 text-sm font-medium">{value || '—'}</dd>
        </div>
    );
}

export function CountBadge({ count }: { count: number }) {
    return count ? (
        <Badge variant="secondary" className="tabular-nums">
            {count}
        </Badge>
    ) : null;
}
