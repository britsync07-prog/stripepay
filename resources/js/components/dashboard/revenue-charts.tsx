import { usePage } from '@inertiajs/react';
import { DollarSign } from 'lucide-react';
import {
    Area,
    AreaChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

import { EmptyState } from '@/components/empty-state';
import { Panel } from '@/components/panel';
import { Reveal } from '@/components/reveal';

import { BarList } from './bar-list';
import type { PlanRevenueRow } from './types';

export function RevenueCharts({
    revenueSeries,
    revenueByPlan,
    rangeLabel,
}: {
    revenueSeries: { month: string; total: number }[];
    revenueByPlan: PlanRevenueRow[];
    rangeLabel: string;
}) {
    const { symbol } = usePage().props.currency;
    const money = (value: number) => `${symbol}${value.toLocaleString()}`;

    return (
        <div className="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
            <Reveal delay={90}>
                <Panel title="Revenue over time" className="h-full">
                    <div className="h-64">
                        <ResponsiveContainer width="100%" height="100%">
                            <AreaChart
                                data={revenueSeries}
                                margin={{
                                    top: 8,
                                    right: 8,
                                    bottom: 8,
                                    left: -8,
                                }}
                            >
                                <defs>
                                    <linearGradient
                                        id="revArea"
                                        x1="0"
                                        y1="0"
                                        x2="0"
                                        y2="1"
                                    >
                                        <stop
                                            offset="0%"
                                            stopColor="var(--primary)"
                                            stopOpacity={0.35}
                                        />
                                        <stop
                                            offset="100%"
                                            stopColor="var(--primary)"
                                            stopOpacity={0.02}
                                        />
                                    </linearGradient>
                                </defs>
                                <CartesianGrid
                                    strokeDasharray="3 3"
                                    stroke="var(--border)"
                                    vertical={false}
                                />
                                <XAxis
                                    dataKey="month"
                                    tick={{
                                        fontSize: 11,
                                        fill: 'var(--muted-foreground)',
                                    }}
                                    tickLine={false}
                                    axisLine={false}
                                />
                                <YAxis
                                    tick={{
                                        fontSize: 11,
                                        fill: 'var(--muted-foreground)',
                                    }}
                                    tickLine={false}
                                    axisLine={false}
                                    width={56}
                                    tickFormatter={money}
                                />
                                <Tooltip
                                    contentStyle={{
                                        background: 'var(--popover)',
                                        border: '1px solid var(--border)',
                                        borderRadius: 10,
                                        boxShadow: 'var(--shadow-elevated)',
                                        fontSize: 12,
                                    }}
                                    formatter={(value) => [
                                        money(Number(value)),
                                        'Revenue',
                                    ]}
                                />
                                <Area
                                    type="monotone"
                                    dataKey="total"
                                    stroke="var(--primary)"
                                    strokeWidth={2}
                                    fill="url(#revArea)"
                                />
                            </AreaChart>
                        </ResponsiveContainer>
                    </div>
                </Panel>
            </Reveal>

            <Reveal delay={120}>
                <Panel
                    title="Revenue by plan"
                    eyebrow={rangeLabel}
                    className="h-full"
                >
                    {revenueByPlan.length === 0 ? (
                        <EmptyState
                            icon={DollarSign}
                            title="No revenue yet"
                            description="Paid consultations will appear here once clients start booking."
                            className="py-12"
                        />
                    ) : (
                        <BarList
                            rows={revenueByPlan.map((p) => ({
                                label: p.plan,
                                raw: p.total,
                                display: p.total_label,
                            }))}
                            valueClassName="w-20"
                        />
                    )}
                </Panel>
            </Reveal>
        </div>
    );
}
