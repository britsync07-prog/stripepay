import { Link } from '@inertiajs/react';
import { ArrowRight, CheckCircle2 } from 'lucide-react';

import { Panel } from '@/components/panel';
import { cn } from '@/lib/utils';
import { consultations, followUps, payouts } from '@/routes/admin';

const ITEM_LINKS: Record<string, () => string> = {
    price: () =>
        consultations.url({
            query: { filter: { type: 'requests', status: 'pending' } },
        }),
    followups: () => followUps.url(),
    assign: () =>
        consultations.url({
            query: { filter: { status: 'paid', assigned: 'no' } },
        }),
    payment: () =>
        consultations.url({
            query: { filter: { status: 'pending', type: 'consultations' } },
        }),
    payouts: () => payouts.url(),
};

export function NeedsAttention({
    items,
}: {
    items: { key: string; count: number; label: string }[];
}) {
    return (
        <Panel title="Needs attention" eyebrow="Action queue">
            {items.every((i) => i.count === 0) ? (
                <div className="flex items-center gap-2 py-1 text-sm text-muted-foreground">
                    <CheckCircle2 className="size-4 text-success" />
                    All caught up — nothing needs action right now.
                </div>
            ) : (
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    {items.map((item) => {
                        const active = item.count > 0;
                        const target =
                            ITEM_LINKS[item.key] ?? (() => consultations.url());

                        return (
                            <Link
                                key={item.key}
                                href={target()}
                                className={cn(
                                    'group flex items-center gap-3 rounded-xl border px-4 py-3 transition-shadow',
                                    active
                                        ? 'bg-warning/[0.08] hover:shadow-elevated'
                                        : 'opacity-55',
                                )}
                            >
                                <span
                                    className={cn(
                                        'font-mono text-2xl font-bold tabular-nums',
                                        active
                                            ? 'text-warning-foreground'
                                            : 'text-muted-foreground',
                                    )}
                                >
                                    {item.count}
                                </span>
                                <span className="flex-1 text-sm leading-tight font-medium">
                                    {item.label}
                                </span>
                                {active && (
                                    <ArrowRight className="size-4 text-muted-foreground transition-transform group-hover:translate-x-0.5" />
                                )}
                            </Link>
                        );
                    })}
                </div>
            )}
        </Panel>
    );
}
