import { Head, Link } from '@inertiajs/react';
import {
    Activity,
    ArrowRight,
    CheckCircle2,
    Clock,
    FileText,
    Plus,
    Receipt,
    Sparkles,
    Wallet,
} from 'lucide-react';

import type { LucideIcon } from 'lucide-react';
import { ConsultationStatusBadge } from '@/components/consultation-status-badge';
import { EmptyState } from '@/components/empty-state';
import { LedgerCell } from '@/components/ledger-cell';
import { PageHeader } from '@/components/page-header';
import { Panel, panelLinkClass } from '@/components/panel';
import { Reveal } from '@/components/reveal';
import { Button } from '@/components/ui/button';
import { customPlan } from '@/routes';
import { consultation, consultations } from '@/routes/portal';
import type { ClientConsultation } from '@/types';

interface OutstandingRequest {
    uuid: string;
    title: string;
    amount: string;
    expiresAt: string | null;
    payUrl: string;
}

interface ActiveEngagement {
    uuid: string;
    title: string;
    work_status: string;
    work_status_label: string;
    updated: string | null;
}

interface Props {
    client: { name: string; first_name: string };
    stats: { total: number; paid: number; pending: number; spent: string };
    recent: ClientConsultation[];
    active: ActiveEngagement[];
    outstanding: OutstandingRequest[];
}

export default function ClientDashboard({
    client,
    stats,
    recent,
    active,
    outstanding,
}: Props) {
    const cards: {
        label: string;
        value: string | number;
        icon: LucideIcon;
        iconClass: string;
    }[] = [
        {
            label: 'Consultations',
            value: stats.total,
            icon: FileText,
            iconClass: 'text-primary',
        },
        {
            label: 'Active — paid',
            value: stats.paid,
            icon: CheckCircle2,
            iconClass: 'text-success',
        },
        {
            label: 'Pending',
            value: stats.pending,
            icon: Clock,
            iconClass: 'text-warning',
        },
        {
            label: 'Total spent',
            value: stats.spent,
            icon: Wallet,
            iconClass: 'text-info',
        },
    ];

    return (
        <>
            <Head title="Dashboard" />
            <div className="space-y-8">
                <PageHeader
                    eyebrow="Client portal"
                    tag="Overview"
                    title={
                        <>
                            Welcome back,{' '}
                            <em className="text-gold-gradient">
                                {client.first_name}
                            </em>
                        </>
                    }
                    description="Here's an overview of your engagements with BlackDesck."
                    actions={
                        <>
                            <Button asChild variant="outline">
                                <Link href={customPlan()}>
                                    <Sparkles className="size-4" /> Request
                                    custom plan
                                </Link>
                            </Button>
                            <Button asChild>
                                <Link href="/" className="group">
                                    <Plus className="size-4" /> New consultation
                                </Link>
                            </Button>
                        </>
                    }
                />

                <Reveal delay={60}>
                    <div className="grid grid-cols-2 surface-ledger lg:grid-cols-4">
                        {cards.map((c) => (
                            <LedgerCell key={c.label} {...c} />
                        ))}
                    </div>
                </Reveal>

                {active.length > 0 && (
                    <Reveal delay={90}>
                        <div className="overflow-hidden surface-card">
                            <div className="flex items-center gap-2.5 border-b border-border/70 px-5 py-3.5">
                                <Activity className="size-4 text-primary" />
                                <h2 className="font-serif text-base font-semibold">
                                    {active.length === 1
                                        ? '1 engagement in progress'
                                        : `${active.length} engagements in progress`}
                                </h2>
                            </div>
                            <ul className="divide-y divide-border/70">
                                {active.map((a) => (
                                    <li key={a.uuid}>
                                        <Link
                                            href={consultation(a.uuid)}
                                            className="group/row flex items-center gap-4 px-5 py-4 transition-colors hover:bg-muted/40"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate font-medium">
                                                    {a.title}
                                                </p>
                                                {a.updated && (
                                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                                        Updated {a.updated}
                                                    </p>
                                                )}
                                            </div>
                                            <ConsultationStatusBadge
                                                status={a.work_status}
                                                label={a.work_status_label}
                                            />
                                            <ArrowRight className="size-4 shrink-0 text-muted-foreground transition-transform group-hover/row:translate-x-0.5 group-hover/row:text-foreground" />
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </Reveal>
                )}

                {outstanding.length > 0 && (
                    <Reveal delay={100}>
                        <div className="overflow-hidden rounded-2xl border border-primary/30 bg-primary/5 shadow-card edge-highlight">
                            <div className="flex items-center gap-2.5 border-b border-primary/20 px-5 py-3.5">
                                <Receipt className="size-4 text-primary" />
                                <h2 className="font-serif text-base font-semibold">
                                    Outstanding payments
                                </h2>
                            </div>
                            <ul className="divide-y divide-primary/15">
                                {outstanding.map((o) => (
                                    <li
                                        key={o.uuid}
                                        className="flex flex-wrap items-center justify-between gap-3 px-5 py-4"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">
                                                {o.title}
                                            </p>
                                            {o.expiresAt && (
                                                <p className="mt-0.5 flex items-center gap-1.5 text-xs text-muted-foreground">
                                                    <Clock className="size-3.5" />
                                                    Pay by {o.expiresAt}
                                                </p>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <span className="font-mono text-lg font-semibold tabular-nums">
                                                {o.amount}
                                            </span>
                                            <Button asChild size="sm">
                                                <a href={o.payUrl}>
                                                    Pay now
                                                    <ArrowRight className="size-4" />
                                                </a>
                                            </Button>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </Reveal>
                )}

                <Reveal delay={120}>
                    <Panel
                        flush
                        title="Recent consultations"
                        action={
                            recent.length > 0 ? (
                                <Link
                                    href={consultations()}
                                    className={`group/all ${panelLinkClass}`}
                                >
                                    View all
                                    <ArrowRight className="size-3.5 transition-transform group-hover/all:translate-x-0.5" />
                                </Link>
                            ) : undefined
                        }
                    >
                        {recent.length === 0 ? (
                            <EmptyState
                                icon={Receipt}
                                title="No consultations yet"
                                description="Your bookings will appear here once you get started."
                                action={
                                    <Button asChild>
                                        <Link href="/">Browse plans</Link>
                                    </Button>
                                }
                                className="py-16"
                            />
                        ) : (
                            <ul className="divide-y divide-border/70">
                                {recent.map((c) => (
                                    <li key={c.uuid}>
                                        <Link
                                            href={consultation(c.uuid)}
                                            className="group/row flex items-center gap-4 px-5 py-4 transition-colors hover:bg-muted/40"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate font-medium">
                                                    {c.plan}
                                                </p>
                                                <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                                                    {c.reference} ·{' '}
                                                    {c.date ?? '—'}
                                                </p>
                                            </div>
                                            <span className="font-mono text-sm font-medium tabular-nums">
                                                {c.amount}
                                            </span>
                                            <ConsultationStatusBadge
                                                status={c.status}
                                                label={c.status_label}
                                            />
                                            <ArrowRight className="size-4 shrink-0 text-muted-foreground transition-transform group-hover/row:translate-x-0.5 group-hover/row:text-foreground" />
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Panel>
                </Reveal>
            </div>
        </>
    );
}
