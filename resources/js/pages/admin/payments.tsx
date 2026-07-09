import { Head, Link } from '@inertiajs/react';
import { Banknote, Clock, CreditCard, Eye } from 'lucide-react';

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
import { RowActionLink, RowActions } from '@/components/row-actions';
import { StatCard } from '@/components/stat-card';
import { consultation, payments as paymentsRoute } from '@/routes/admin';
import type { Paginated, Payment } from '@/types';

interface Props {
    payments: Paginated<Payment>;
    filters: { search: string; status: string };
    stats: { received: string; total: number; refunded: number };
    statusOptions: { value: string; label: string }[];
}

export default function AdminPayments({
    payments,
    filters,
    stats,
    statusOptions,
}: Props) {
    const rows = payments.data;

    return (
        <>
            <Head title="Payments" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Transactions"
                    title="Payments"
                    description="Money received from clients across all gateways."
                    actions={<ExportButton path="/admin/payments/export" />}
                />

                <div className="grid gap-3 sm:grid-cols-3">
                    <StatCard
                        compact
                        label="Total received"
                        value={stats.received}
                        icon={Banknote}
                        tone="primary"
                    />
                    <StatCard
                        compact
                        label="Transactions"
                        value={stats.total}
                        icon={CreditCard}
                        tone="info"
                    />
                    <StatCard
                        compact
                        label="Refunded"
                        value={stats.refunded}
                        icon={Clock}
                        tone="warning"
                    />
                </div>

                <DataTable
                    toolbar={
                        <ListFilters
                            url={paymentsRoute().url}
                            search={filters.search}
                            status={filters.status}
                            statusOptions={statusOptions}
                            searchPlaceholder="Search by plan, gateway or reference…"
                        />
                    }
                    footer={
                        payments.total > 0 ? (
                            <Pagination
                                links={payments.links}
                                from={payments.from}
                                to={payments.to}
                                total={payments.total}
                            />
                        ) : undefined
                    }
                >
                    <THead>
                        <tr>
                            <TH>Plan</TH>
                            <TH align="right">Amount</TH>
                            <TH>Gateway</TH>
                            <TH>Reference</TH>
                            <TH>Status</TH>
                            <TH>Date</TH>
                            <TH>
                                <span className="sr-only">Actions</span>
                            </TH>
                        </tr>
                    </THead>
                    <TBody>
                        {rows.length === 0 ? (
                            <TableEmpty
                                colSpan={7}
                                icon={CreditCard}
                                title="No payments found"
                                description="Settled transactions will be recorded here."
                            />
                        ) : (
                            rows.map((p, i) => (
                                <TR
                                    key={i}
                                    href={
                                        p.consultation
                                            ? consultation(p.consultation).url
                                            : undefined
                                    }
                                >
                                    <TD className="font-medium">
                                        {p.consultation ? (
                                            <Link
                                                href={consultation(
                                                    p.consultation,
                                                )}
                                                className="hover:text-primary hover:underline"
                                            >
                                                {p.plan}
                                            </Link>
                                        ) : (
                                            p.plan
                                        )}
                                    </TD>
                                    <TD
                                        align="right"
                                        className="font-mono tabular-nums"
                                    >
                                        {p.amount}
                                    </TD>
                                    <TD className="capitalize">{p.gateway}</TD>
                                    <TD className="font-mono text-xs text-muted-foreground">
                                        {p.reference ?? '—'}
                                    </TD>
                                    <TD>
                                        <ConsultationStatusBadge
                                            status={p.status}
                                            label={p.status}
                                        />
                                    </TD>
                                    <TD className="text-muted-foreground">
                                        {p.date ?? '—'}
                                    </TD>
                                    <TD align="right">
                                        {p.consultation && (
                                            <RowActions>
                                                <RowActionLink
                                                    href={consultation(
                                                        p.consultation,
                                                    )}
                                                    icon={Eye}
                                                    label="View consultation"
                                                />
                                            </RowActions>
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
