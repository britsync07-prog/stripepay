import { Deferred, Head } from '@inertiajs/react';
import {
    CheckCircle2,
    FolderKanban,
    UserRound,
    Users,
    Wallet,
} from 'lucide-react';

import type { LucideIcon } from 'lucide-react';
import { CountriesPanel } from '@/components/dashboard/countries-panel';
import { FinancialsPanel } from '@/components/dashboard/financials-panel';
import { LeadsPipelinePanel } from '@/components/dashboard/leads-pipeline-panel';
import { NeedsAttention } from '@/components/dashboard/needs-attention';
import { RangeSelect } from '@/components/dashboard/range-select';
import { RecentConsultations } from '@/components/dashboard/recent-consultations';
import { RevenueCharts } from '@/components/dashboard/revenue-charts';
import type {
    Composition,
    GatewayRow,
    LeadsPipeline,
    PlanRevenueRow,
    RangeKey,
    Stats,
} from '@/components/dashboard/types';
import { RANGE_LABELS } from '@/components/dashboard/types';
import { LedgerCell } from '@/components/ledger-cell';
import { PageHeader } from '@/components/page-header';
import { Reveal } from '@/components/reveal';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import type { Consultation } from '@/types';

interface Props {
    range: RangeKey;
    stats: Stats;
    needsAttention: { key: string; count: number; label: string }[];
    leadsPipeline: LeadsPipeline;
    composition: Composition;
    gatewayBreakdown: GatewayRow[];
    revenueByPlan?: PlanRevenueRow[];
    revenueSeries?: { month: string; total: number }[];
    countries?: { name: string; total: number }[];
    recent?: Consultation[];
}

const PanelSkeleton = ({ className = 'h-72' }: { className?: string }) => (
    <Skeleton className={`${className} w-full rounded-2xl`} />
);

export default function AdminDashboard({
    range,
    stats,
    needsAttention,
    leadsPipeline,
    composition,
    gatewayBreakdown,
    revenueByPlan,
    revenueSeries,
    countries,
    recent,
}: Props) {
    const rangeLabel = RANGE_LABELS[range];

    const figures: {
        label: string;
        value: string | number;
        icon: LucideIcon;
        iconClass: string;
    }[] = [
        {
            label: 'Avg deal',
            value: stats.avgDeal,
            icon: Wallet,
            iconClass: 'text-primary',
        },
        {
            label: 'Clients',
            value: stats.clients,
            icon: Users,
            iconClass: 'text-info',
        },
        {
            label: 'Consultants',
            value: stats.consultants,
            icon: UserRound,
            iconClass: 'text-brand-accent',
        },
        {
            label: 'Active projects',
            value: stats.projectsActive,
            icon: FolderKanban,
            iconClass: 'text-warning',
        },
        {
            label: 'Completed',
            value: stats.projectsCompleted,
            icon: CheckCircle2,
            iconClass: 'text-success',
        },
    ];

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Admin console"
                    tag="Overview"
                    title={
                        <>
                            Platform{' '}
                            <em className="text-gold-gradient">overview</em>
                        </>
                    }
                    description="A live snapshot of revenue, engagements, and clients across the platform."
                    actions={<RangeSelect range={range} />}
                />

                <Reveal>
                    <div className="grid grid-cols-2 surface-ledger lg:grid-cols-5">
                        {figures.map((f, i) => (
                            <LedgerCell
                                key={f.label}
                                {...f}
                                className={cn(
                                    i === figures.length - 1 &&
                                        'col-span-2 lg:col-span-1',
                                )}
                            />
                        ))}
                    </div>
                </Reveal>

                <Reveal delay={40}>
                    <NeedsAttention items={needsAttention} />
                </Reveal>

                <Reveal delay={60}>
                    <FinancialsPanel
                        composition={composition}
                        gatewayBreakdown={gatewayBreakdown}
                        receivedThisMonth={stats.receivedThisMonth}
                        receivedDelta={stats.receivedDelta}
                        rangeLabel={rangeLabel}
                    />
                </Reveal>

                <Reveal delay={90}>
                    <LeadsPipelinePanel pipeline={leadsPipeline} />
                </Reveal>

                <Deferred
                    data={['revenueSeries', 'revenueByPlan']}
                    fallback={
                        <div className="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
                            <PanelSkeleton className="h-80" />
                            <PanelSkeleton className="h-80" />
                        </div>
                    }
                >
                    <RevenueCharts
                        revenueSeries={revenueSeries ?? []}
                        revenueByPlan={revenueByPlan ?? []}
                        rangeLabel={rangeLabel}
                    />
                </Deferred>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Deferred data="countries" fallback={<PanelSkeleton />}>
                        <Reveal delay={150} className="h-full">
                            <CountriesPanel countries={countries ?? []} />
                        </Reveal>
                    </Deferred>
                    <Deferred data="recent" fallback={<PanelSkeleton />}>
                        <Reveal delay={180} className="h-full">
                            <RecentConsultations recent={recent ?? []} />
                        </Reveal>
                    </Deferred>
                </div>
            </div>
        </>
    );
}
