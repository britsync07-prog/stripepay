import type { LucideIcon } from 'lucide-react';

import { cn } from '@/lib/utils';

export type StatTone = 'primary' | 'success' | 'warning' | 'info';

const toneText: Record<StatTone, string> = {
    primary: 'text-primary',
    success: 'text-success',
    warning: 'text-warning',
    info: 'text-info',
};

/**
 * Clean, standard metric card: a muted label + small tone-tinted icon on top,
 * a large mono figure below. No chips, lifts, or gradients — calm and uniform
 * across the dashboards and list pages. `compact` trims the padding/figure size.
 */
export function StatCard({
    label,
    value,
    icon: Icon,
    tone = 'primary',
    hint,
    compact = false,
    className,
}: {
    label: string;
    value: string | number;
    icon: LucideIcon;
    tone?: StatTone;
    hint?: string;
    compact?: boolean;
    className?: string;
}) {
    return (
        <div className={cn('surface-tile', compact ? 'p-4' : 'p-5', className)}>
            <div className="flex items-center justify-between gap-2">
                <p className="truncate text-microcaps text-muted-foreground">
                    {label}
                </p>
                <Icon className={cn('size-4 shrink-0', toneText[tone])} />
            </div>
            <p
                className={cn(
                    'mt-2 font-mono font-semibold tracking-tight tabular-nums',
                    compact ? 'text-xl' : 'text-2xl',
                )}
            >
                {value}
            </p>
            {hint && (
                <p className="mt-1 text-xs text-muted-foreground">{hint}</p>
            )}
        </div>
    );
}
