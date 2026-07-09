import { cn } from '@/lib/utils';

export type BarRow = { label: string; raw: number; display: string };

/** Horizontal proportion bars — one row per item, widths relative to the max. */
export function BarList({
    rows,
    valueClassName,
}: {
    rows: BarRow[];
    valueClassName?: string;
}) {
    const max = Math.max(1, ...rows.map((r) => r.raw));

    return (
        <ul className="space-y-3">
            {rows.map((r) => (
                <li key={r.label} className="flex items-center gap-3">
                    <span className="w-40 shrink-0 truncate text-sm font-medium">
                        {r.label}
                    </span>
                    <span className="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                        <span
                            className="block h-full rounded-full bg-primary"
                            style={{
                                width: `${Math.round((r.raw / max) * 100)}%`,
                            }}
                        />
                    </span>
                    <span
                        className={cn(
                            'shrink-0 text-right font-mono text-sm text-muted-foreground tabular-nums',
                            valueClassName ?? 'w-8',
                        )}
                    >
                        {r.display}
                    </span>
                </li>
            ))}
        </ul>
    );
}
