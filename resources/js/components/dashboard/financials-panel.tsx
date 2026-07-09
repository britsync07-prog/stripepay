import { TrendingUp } from 'lucide-react';

import { Panel } from '@/components/panel';
import { Badge } from '@/components/ui/badge';
import { FINANCE_SEGMENTS } from '@/constants/dashboard';
import { cn } from '@/lib/utils';

import { CompositionBar, LegendPill, pctOf } from './composition-bar';
import type { Composition, GatewayRow } from './types';

export function FinancialsPanel({
    composition,
    gatewayBreakdown,
    receivedThisMonth,
    receivedDelta,
    rangeLabel,
}: {
    composition: Composition;
    gatewayBreakdown: GatewayRow[];
    receivedThisMonth: string;
    receivedDelta: number | null;
    rangeLabel: string;
}) {
    return (
        <Panel title="Where your money goes" eyebrow={rangeLabel}>
            <div className="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p className="text-xs tracking-wide text-muted-foreground">
                        Received · {rangeLabel.toLowerCase()}
                    </p>
                    <p className="font-serif text-4xl font-semibold tabular-nums">
                        {composition.received_label}
                    </p>
                    <p className="mt-1 text-xs text-muted-foreground">
                        {receivedThisMonth} this month
                        {receivedDelta !== null && (
                            <span
                                className={cn(
                                    'ml-1 font-medium',
                                    receivedDelta >= 0
                                        ? 'text-success'
                                        : 'text-destructive',
                                )}
                            >
                                ({receivedDelta >= 0 ? '+' : ''}
                                {receivedDelta}% vs last month)
                            </span>
                        )}
                    </p>
                </div>
                <Badge
                    variant="outline"
                    className="gap-1.5 rounded-full border-transparent bg-success/10 px-3 py-1 text-sm font-semibold text-success ring-1 ring-success/20 [&>svg]:size-3.5"
                >
                    <TrendingUp />
                    {pctOf(composition.netMargin, composition.received)}% kept
                </Badge>
            </div>

            <CompositionBar
                total={composition.received}
                segments={[
                    {
                        value: composition.netMargin,
                        className: FINANCE_SEGMENTS.netMargin.gradient,
                        label: 'Net margin',
                    },
                    {
                        value: composition.tax,
                        className: FINANCE_SEGMENTS.tax.gradient,
                        label: 'Tax',
                    },
                    {
                        value: composition.fees,
                        className: FINANCE_SEGMENTS.fees.gradient,
                        label: 'Fees',
                    },
                    {
                        value: composition.paidOut,
                        className: FINANCE_SEGMENTS.payouts.gradient,
                        label: 'Payouts',
                    },
                ]}
            />

            <div className="mt-4 grid grid-cols-2 gap-2 lg:grid-cols-4">
                <LegendPill
                    dotClass={FINANCE_SEGMENTS.netMargin.dot}
                    label="Net margin"
                    value={composition.netMargin_label}
                    pct={pctOf(composition.netMargin, composition.received)}
                />
                <LegendPill
                    dotClass={FINANCE_SEGMENTS.tax.dot}
                    label="Tax"
                    value={composition.tax_label}
                    pct={pctOf(composition.tax, composition.received)}
                />
                <LegendPill
                    dotClass={FINANCE_SEGMENTS.fees.dot}
                    label="Fees"
                    value={composition.fees_label}
                    pct={pctOf(composition.fees, composition.received)}
                />
                <LegendPill
                    dotClass={FINANCE_SEGMENTS.payouts.dot}
                    label="Payouts"
                    value={composition.paidOut_label}
                    pct={pctOf(composition.paidOut, composition.received)}
                />
            </div>

            {gatewayBreakdown.length > 0 && (
                <div className="mt-6 space-y-4 border-t pt-5">
                    <p className="text-eyebrow text-muted-foreground">
                        Per gateway
                    </p>
                    {gatewayBreakdown.map((g) => (
                        <div key={g.gateway}>
                            <div className="mb-1.5 flex items-center justify-between text-sm">
                                <span className="font-medium">
                                    {g.label}{' '}
                                    <span className="font-mono text-muted-foreground tabular-nums">
                                        {g.gross}
                                    </span>
                                </span>
                                <span className="font-mono text-xs tabular-nums">
                                    <span className="text-success">
                                        net {g.net}
                                    </span>{' '}
                                    ·{' '}
                                    <span className="text-warning-foreground">
                                        fee {g.fees}
                                    </span>
                                </span>
                            </div>
                            <CompositionBar
                                small
                                total={g.grossRaw}
                                segments={[
                                    {
                                        value: g.netRaw,
                                        className:
                                            FINANCE_SEGMENTS.netMargin.gradient,
                                    },
                                    {
                                        value: g.feesRaw,
                                        className:
                                            FINANCE_SEGMENTS.fees.gradient,
                                    },
                                ]}
                            />
                        </div>
                    ))}
                </div>
            )}
        </Panel>
    );
}
