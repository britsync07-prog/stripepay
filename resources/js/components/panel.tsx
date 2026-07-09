import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

/**
 * Shared elevated section card — the house "surface" used across admin + portal.
 * Renders the warm-paper card shell (the `surface-card` utility from app.css)
 * with an optional serif title / gold eyebrow / action header.
 *
 * Use `flush` when the body is a table or edge-to-edge list (drops body padding
 * so rows can run to the card border); otherwise the body gets comfortable
 * padding. Compose header actions with the exported `panelLinkClass` to keep
 * "View all →" links on-brand and SPA-navigating.
 */
export function Panel({
    eyebrow,
    title,
    description,
    action,
    flush = false,
    className,
    bodyClassName,
    children,
}: {
    eyebrow?: string;
    title?: ReactNode;
    description?: ReactNode;
    action?: ReactNode;
    flush?: boolean;
    className?: string;
    bodyClassName?: string;
    children: ReactNode;
}) {
    const hasHeader = Boolean(eyebrow || title || description || action);

    return (
        <section className={cn('overflow-hidden surface-card', className)}>
            {hasHeader && (
                <header className="flex flex-wrap items-end justify-between gap-3 border-b bg-gradient-to-b from-muted/35 to-transparent px-5 py-4">
                    <div className="min-w-0">
                        {eyebrow && (
                            <span className="mb-1 inline-flex items-center gap-2 text-eyebrow text-primary">
                                <span className="h-px w-4 bg-primary/50" />
                                {eyebrow}
                            </span>
                        )}
                        {title && (
                            <h2 className="font-serif text-lg font-semibold tracking-tight text-balance">
                                {title}
                            </h2>
                        )}
                        {description && (
                            <p className="mt-0.5 text-sm text-muted-foreground">
                                {description}
                            </p>
                        )}
                    </div>
                    {action && (
                        <div className="flex shrink-0 items-center gap-2">
                            {action}
                        </div>
                    )}
                </header>
            )}
            <div className={cn(!flush && 'p-5', bodyClassName)}>{children}</div>
        </section>
    );
}

/** On-brand link styling for Panel header actions (e.g. "View all →"). */
export const panelLinkClass =
    'inline-flex items-center gap-1 text-sm font-medium text-primary transition-colors hover:text-primary/80';
