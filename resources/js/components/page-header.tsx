import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

/**
 * Standardised dossier page header — mono eyebrow + fading hairline (with an
 * optional right-hand tag), serif display title, description, and an actions
 * slot. Animates in on mount (fade-up). Used by every admin + portal page so
 * the whole platform shares one header structure.
 */
export function PageHeader({
    eyebrow,
    tag,
    title,
    description,
    actions,
    className,
}: {
    eyebrow?: string;
    tag?: string;
    title: ReactNode;
    description?: ReactNode;
    actions?: ReactNode;
    className?: string;
}) {
    return (
        <div className={cn('animate-fade-up', className)}>
            {eyebrow && (
                <div className="flex items-center gap-4">
                    <span className="text-kicker text-primary">{eyebrow}</span>
                    <span aria-hidden className="hairline-fade flex-1" />
                    {tag && (
                        <span className="text-eyebrow text-brand-accent">
                            {tag}
                        </span>
                    )}
                </div>
            )}
            <div
                className={cn(
                    'flex flex-wrap items-end justify-between gap-4',
                    eyebrow && 'mt-5',
                )}
            >
                <div className="min-w-0">
                    <h1 className="font-serif text-2xl font-semibold tracking-tight text-balance sm:text-3xl">
                        {title}
                    </h1>
                    {description && (
                        <p className="mt-1.5 max-w-2xl text-sm text-muted-foreground">
                            {description}
                        </p>
                    )}
                </div>
                {actions && (
                    <div className="flex shrink-0 items-center gap-2">
                        {actions}
                    </div>
                )}
            </div>
        </div>
    );
}
