import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

/**
 * Centred empty / zero-results placeholder with an icon medallion and an
 * optional call to action. Replaces the bare "No X yet." strings.
 */
export function EmptyState({
    icon: Icon,
    title,
    description,
    action,
    className,
}: {
    icon: LucideIcon;
    title: string;
    description?: string;
    action?: ReactNode;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center px-6 py-16 text-center',
                className,
            )}
        >
            <span className="flex size-12 items-center justify-center rounded-2xl bg-muted ring-1 ring-border ring-inset">
                <Icon className="size-6 text-muted-foreground" />
            </span>
            <p className="mt-4 font-medium">{title}</p>
            {description && (
                <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                    {description}
                </p>
            )}
            {action && <div className="mt-5">{action}</div>}
        </div>
    );
}
