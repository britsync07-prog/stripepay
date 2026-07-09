import { router } from '@inertiajs/react';

import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';

import { RANGE_OPTIONS } from './types';
import type { RangeKey } from './types';

/** Segmented control scoping the dashboard's money figures to a time window. */
export function RangeSelect({ range }: { range: RangeKey }) {
    const select = (value: RangeKey) => {
        if (value === range) {
            return;
        }

        router.get(dashboard().url, value === 'all' ? {} : { range: value }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    return (
        <div
            role="group"
            aria-label="Time range"
            className="inline-flex rounded-lg border bg-card p-0.5 shadow-card"
        >
            {RANGE_OPTIONS.map((o) => (
                <button
                    key={o.value}
                    type="button"
                    onClick={() => select(o.value)}
                    aria-pressed={o.value === range}
                    className={cn(
                        'rounded-md px-3 py-1.5 font-mono text-xs font-medium transition-colors',
                        o.value === range
                            ? 'bg-primary text-primary-foreground shadow-xs'
                            : 'text-muted-foreground hover:text-foreground',
                    )}
                >
                    {o.label}
                </button>
            ))}
        </div>
    );
}
