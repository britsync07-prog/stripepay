import { Link } from '@inertiajs/react';

import { cn } from '@/lib/utils';
import type { PaginationLink } from '@/types';

/**
 * Server-side pagination control. Renders Laravel's paginator `links` as Inertia
 * links (preserving scroll + filter state). Only one page of rows is ever loaded.
 */
export function Pagination({
    links,
    from,
    to,
    total,
}: {
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}) {
    if (total === 0) {
        return null;
    }

    return (
        <div className="flex flex-col items-center justify-between gap-3 sm:flex-row">
            <p className="text-sm text-muted-foreground">
                Showing{' '}
                <span className="font-medium text-foreground">{from ?? 0}</span>
                –<span className="font-medium text-foreground">{to ?? 0}</span>{' '}
                of <span className="font-medium text-foreground">{total}</span>
            </p>

            {links.length > 3 && (
                <nav className="flex flex-wrap items-center gap-1">
                    {links.map((link, i) =>
                        link.url ? (
                            <Link
                                key={i}
                                href={link.url}
                                preserveScroll
                                preserveState
                                className={cn(
                                    'inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm transition-colors',
                                    link.active
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'hover:border-primary/40 hover:bg-primary/10 hover:text-primary',
                                )}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ) : (
                            <span
                                key={i}
                                className="inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm text-muted-foreground opacity-40"
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ),
                    )}
                </nav>
            )}
        </div>
    );
}
