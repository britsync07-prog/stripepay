import type { LucideIcon } from 'lucide-react';

import { cn } from '@/lib/utils';

/**
 * One cell of a `surface-ledger` stat band — mono microcaps label, tone icon,
 * large tabular figure. Compose inside `grid grid-cols-* surface-ledger`;
 * the cell supplies its own `bg-card` so the grid's border shows as seams.
 */
export function LedgerCell({
    label,
    value,
    icon: Icon,
    iconClass = 'text-primary',
    className,
}: {
    label: string;
    value: string | number;
    icon: LucideIcon;
    iconClass?: string;
    className?: string;
}) {
    return (
        <div className={cn('bg-card px-5 py-5', className)}>
            <div className="flex items-center justify-between gap-2">
                <p className="truncate text-microcaps text-muted-foreground">
                    {label}
                </p>
                <Icon className={cn('size-4 shrink-0', iconClass)} />
            </div>
            <p className="mt-2.5 font-mono text-2xl font-semibold tracking-tight tabular-nums">
                {value}
            </p>
        </div>
    );
}
