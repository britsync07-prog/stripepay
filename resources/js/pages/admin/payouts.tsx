import { Head, Link } from '@inertiajs/react';
import { Banknote, Clock, Paperclip, Receipt } from 'lucide-react';

import { ConsultationStatusBadge } from '@/components/consultation-status-badge';
import {
    DataTable,
    TableEmpty,
    TBody,
    TD,
    TH,
    THead,
    TR,
} from '@/components/data-table';
import { ExportButton } from '@/components/export-button';
import { ListFilters } from '@/components/list-filters';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { payouts as payoutsRoute } from '@/routes/admin';
import { create } from '@/routes/admin/payouts';
import type { Paginated, Payout } from '@/types';

interface Props {
    payouts: Paginated<Payout>;
    filters: { search: string; status: string };
    stats: { paidOut: string; pending: number; total: number };
    statusOptions: { value: string; label: string }[];
}

export default function AdminPayouts({
    payouts,
    filters,
    stats,
    statusOptions,
}: Props) {
    const rows = payouts.data;

    return (
        <>
            <Head title="Payouts" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Expert payouts"
                    title="Payouts"
                    description="Money sent to experts through Stripe."
                    actions={
                        <>
                            <ExportButton path="/admin/payouts/export" />
                            <Button asChild>
                                <Link href={create()}>
                                    <Banknote className="size-4" /> Send payout
                                </Link>
                            </Button>
                        </>
                    }
                />

                <div className="grid gap-3 sm:grid-cols-3">
                    <StatCard
                        compact
                        label="Total paid out"
                        value={stats.paidOut}
                        icon={Banknote}
                        tone="primary"
                    />
                    <StatCard
                        compact
                        label="In progress"
                        value={stats.pending}
                        icon={Clock}
                        tone="warning"
                    />
                    <StatCard
                        compact
                        label="Payouts"
                        value={stats.total}
                        icon={Receipt}
                        tone="info"
                    />
                </div>

                <DataTable
                    toolbar={
                        <ListFilters
                            url={payoutsRoute().url}
                            search={filters.search}
                            status={filters.status}
                            statusOptions={statusOptions}
                            searchPlaceholder="Search by expert or reference…"
                        />
                    }
                    footer={
                        payouts.total > 0 ? (
                            <Pagination
                                links={payouts.links}
                                from={payouts.from}
                                to={payouts.to}
                                total={payouts.total}
                            />
                        ) : undefined
                    }
                >
                    <THead>
                        <tr>
                            <TH>Expert</TH>
                            <TH align="right">Amount</TH>
                            <TH>Method</TH>
                            <TH>Status</TH>
                            <TH>Reference</TH>
                            <TH>Date</TH>
                            <TH>Proof</TH>
                        </tr>
                    </THead>
                    <TBody>
                        {rows.length === 0 ? (
                            <TableEmpty
                                colSpan={7}
                                icon={Banknote}
                                title="No payouts found"
                                description="Send your first payout to an expert to get started."
                            />
                        ) : (
                            rows.map((p) => (
                                <TR key={p.id}>
                                    <TD className="font-medium">
                                        {p.expert ?? '—'}
                                    </TD>
                                    <TD
                                        align="right"
                                        className="font-mono tabular-nums"
                                    >
                                        {p.amount}
                                    </TD>
                                    <TD>{p.method_label}</TD>
                                    <TD>
                                        <ConsultationStatusBadge
                                            status={p.status}
                                            label={p.status_label}
                                        />
                                    </TD>
                                    <TD className="font-mono text-xs text-muted-foreground">
                                        {p.reference ?? '—'}
                                    </TD>
                                    <TD className="text-muted-foreground">
                                        {p.paid_at ?? p.created_at ?? '—'}
                                    </TD>
                                    <TD>
                                        {p.proof_url ? (
                                            <a
                                                href={p.proof_url}
                                                target="_blank"
                                                rel="noopener"
                                                className="inline-flex items-center gap-1 text-primary hover:underline"
                                            >
                                                <Paperclip className="size-3.5" />{' '}
                                                View
                                            </a>
                                        ) : (
                                            '—'
                                        )}
                                    </TD>
                                </TR>
                            ))
                        )}
                    </TBody>
                </DataTable>
            </div>
        </>
    );
}
