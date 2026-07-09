import { cn } from '@/lib/utils';

export type Segment = { value: number; className: string; label?: string };

export const pctOf = (value: number, total: number) =>
    Math.round((value / (total > 0 ? total : 1)) * 100);

/** A single stacked proportion bar: each segment's width is its share of total. */
export function CompositionBar({
    segments,
    total,
    small = false,
}: {
    segments: Segment[];
    total: number;
    small?: boolean;
}) {
    const safeTotal = total > 0 ? total : 1;
    const visible = segments.filter((s) => s.value > 0);

    return (
        <div
            className={cn(
                'flex w-full overflow-hidden rounded-full bg-muted/70 ring-1 ring-black/5 ring-inset',
                small
                    ? 'h-2.5'
                    : 'h-9 shadow-[inset_0_1px_3px_rgba(0,0,0,0.08)]',
            )}
        >
            {visible.map((s, i) => {
                const pct = (s.value / safeTotal) * 100;

                return (
                    <div
                        key={s.label ?? s.className}
                        className={cn(
                            'flex items-center justify-center transition-all',
                            s.className,
                            i < visible.length - 1 &&
                                'border-r border-white/30',
                        )}
                        style={{ width: `${pct}%` }}
                        role={s.label ? 'img' : undefined}
                        aria-label={
                            s.label
                                ? `${s.label} · ${Math.round(pct)}%`
                                : undefined
                        }
                        title={
                            s.label
                                ? `${s.label} · ${Math.round(pct)}%`
                                : undefined
                        }
                    >
                        {!small && pct > 16 && s.label && (
                            <span className="truncate px-2 text-xs font-semibold text-white drop-shadow-sm">
                                {s.label} · {Math.round(pct)}%
                            </span>
                        )}
                    </div>
                );
            })}
        </div>
    );
}

export function LegendPill({
    dotClass,
    label,
    value,
    pct,
}: {
    dotClass: string;
    label: string;
    value: string;
    pct: number;
}) {
    return (
        <div className="flex items-center gap-3 rounded-xl border bg-card/70 px-3.5 py-2.5 shadow-card edge-highlight">
            <span className={cn('size-3 shrink-0 rounded-full', dotClass)} />
            <div className="min-w-0">
                <p className="text-xs text-muted-foreground">{label}</p>
                <p className="font-mono text-sm font-semibold tabular-nums">
                    {value}
                    <span className="ml-1.5 text-xs font-normal text-muted-foreground">
                        {pct}%
                    </span>
                </p>
            </div>
        </div>
    );
}
