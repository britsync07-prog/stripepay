import { MessageSquare } from 'lucide-react';

import { Skeleton } from '@/components/ui/skeleton';
import { CONTACT_META, TRANSITION } from '@/constants/consultation';
import { cn } from '@/lib/utils';
import type { TimelineEvent } from '@/types';

function TimelineRow({ e }: { e: TimelineEvent }) {
    const transition = TRANSITION[e.type];
    const meta = CONTACT_META[e.type];
    const Icon = transition?.icon ?? meta?.icon ?? MessageSquare;
    const chip = transition ? 'bg-primary/10' : (meta?.chip ?? 'bg-muted');
    const text = transition
        ? 'text-primary'
        : (meta?.text ?? 'text-muted-foreground');

    return (
        <li className="flex gap-3">
            <span
                className={cn(
                    'mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full',
                    chip,
                )}
            >
                <Icon className={cn('size-3.5', text)} />
            </span>
            <div className="min-w-0 flex-1">
                <div className="flex items-baseline justify-between gap-2">
                    <p className="min-w-0 flex-1 text-sm break-words">
                        {transition ? (
                            <>
                                <span className="font-medium">
                                    {transition.label}
                                </span>{' '}
                                <span className="text-muted-foreground">
                                    {e.from ?? '—'} → {e.to}
                                </span>
                            </>
                        ) : (
                            <span className="font-medium">
                                {meta?.label ?? 'Note'}
                                {e.duration ? ` · ${e.duration}m` : ''}
                            </span>
                        )}
                        <span className="text-muted-foreground">
                            {' '}
                            · {e.by ?? 'System'}
                        </span>
                    </p>
                    <span className="shrink-0 text-[11px] text-muted-foreground/60">
                        {e.at}
                    </span>
                </div>
                {e.message && (
                    <p className="mt-1.5 rounded-lg border border-border bg-accent px-3 py-2 text-sm break-words text-foreground shadow-xs">
                        {e.message}
                    </p>
                )}
            </div>
        </li>
    );
}

export function TimelineList({
    items,
    empty,
}: {
    items: TimelineEvent[];
    empty: string;
}) {
    if (items.length === 0) {
        return (
            <p className="rounded-xl border border-dashed py-4 text-center text-sm text-muted-foreground">
                {empty}
            </p>
        );
    }

    return (
        <ul className="-mr-1 max-h-72 space-y-4 overflow-y-auto pr-1">
            {items.map((e) => (
                <TimelineRow key={e.id} e={e} />
            ))}
        </ul>
    );
}

export function TimelineSkeleton({ rows = 3 }: { rows?: number }) {
    return (
        <div className="space-y-4">
            {Array.from({ length: rows }).map((_, i) => (
                <div key={i} className="flex gap-3">
                    <Skeleton className="mt-0.5 size-7 shrink-0 rounded-full" />
                    <div className="flex-1 space-y-2">
                        <Skeleton className="h-3.5 w-1/3" />
                        <Skeleton className="h-8 w-3/4 rounded-lg" />
                    </div>
                </div>
            ))}
        </div>
    );
}
