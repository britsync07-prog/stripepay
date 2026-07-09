import { Head, Link } from '@inertiajs/react';
import { CheckCircle2, Eye, FileText, Undo2 } from 'lucide-react';

import {
    DataTable,
    TableEmpty,
    TBody,
    TD,
    TH,
    THead,
    TR,
} from '@/components/data-table';
import { ListFilters } from '@/components/list-filters';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { RowActionLink, RowActions } from '@/components/row-actions';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { invoices as invoicesRoute } from '@/routes/admin';
import { show } from '@/routes/admin/invoices';
import type { InvoiceListItem, Paginated } from '@/types';

interface Props {
    invoices: Paginated<InvoiceListItem>;
    filters: { search: string; status: string };
    statusOptions: { value: string; label: string }[];
    stats: { total: number; paid: number; refunded: number };
}

export default function AdminInvoices({
    invoices,
    filters,
    statusOptions,
    stats,
}: Props) {
    const rows = invoices.data;

    return (
        <>
            <Head title="Invoices" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Finance"
                    title="Invoices"
                    description="Auto-generated when a client pays. Download or share the PDF."
                />

                <div className="grid gap-3 sm:grid-cols-3">
                    <StatCard
                        compact
                        label="Invoices"
                        value={stats.total}
                        icon={FileText}
                        tone="info"
                    />
                    <StatCard
                        compact
                        label="Paid"
                        value={stats.paid}
                        icon={CheckCircle2}
                        tone="success"
                    />
                    <StatCard
                        compact
                        label="Refunded"
                        value={stats.refunded}
                        icon={Undo2}
                        tone="primary"
                    />
                </div>

                <DataTable
                    toolbar={
                        <ListFilters
                            url={invoicesRoute().url}
                            search={filters.search}
                            status={filters.status}
                            statusOptions={statusOptions}
                            searchPlaceholder="Search by number or client…"
                        />
                    }
                    footer={
                        invoices.total > 0 ? (
                            <Pagination
                                links={invoices.links}
                                from={invoices.from}
                                to={invoices.to}
                                total={invoices.total}
                            />
                        ) : undefined
                    }
                >
                    <THead>
                        <tr>
                            <TH>Invoice</TH>
                            <TH>Client</TH>
                            <TH align="right">Total</TH>
                            <TH>Status</TH>
                            <TH>Issued</TH>
                            <TH>
                                <span className="sr-only">Actions</span>
                            </TH>
                        </tr>
                    </THead>
                    <TBody>
                        {rows.length === 0 ? (
                            <TableEmpty
                                colSpan={6}
                                icon={FileText}
                                title="No invoices yet"
                                description="Create an invoice to bill a client."
                            />
                        ) : (
                            rows.map((i) => (
                                <TR key={i.uuid} href={show(i.uuid).url}>
                                    <TD className="font-mono text-xs font-medium">
                                        <Link
                                            href={show(i.uuid)}
                                            className="hover:text-primary hover:underline"
                                        >
                                            {i.reference}
                                        </Link>
                                    </TD>
                                    <TD>{i.client ?? '—'}</TD>
                                    <TD
                                        align="right"
                                        className="font-mono tabular-nums"
                                    >
                                        {i.total}
                                    </TD>
                                    <TD>
                                        <Badge variant={i.status_tone}>
                                            {i.status_label}
                                        </Badge>
                                    </TD>
                                    <TD className="text-muted-foreground">
                                        {i.issued_at ?? '—'}
                                    </TD>
                                    <TD align="right">
                                        <RowActions>
                                            <RowActionLink
                                                href={show(i.uuid)}
                                                icon={Eye}
                                                label="View invoice"
                                            />
                                        </RowActions>
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
