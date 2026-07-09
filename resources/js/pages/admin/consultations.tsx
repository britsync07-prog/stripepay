import { Head } from '@inertiajs/react';
import { Clock, Eye, MessagesSquare, Receipt } from 'lucide-react';

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
import { Badge } from '@/components/ui/badge';
import {
    consultation,
    consultations as consultationsRoute,
} from '@/routes/admin';
import type { Consultation, Paginated } from '@/types';

interface Props {
    consultations: Paginated<Consultation>;
    filters: {
        search: string;
        status: string;
        type: string;
        assigned: string;
    };
    stats: { total: number; requests: number; pending: number };
    statusOptions: { value: string; label: string }[];
}

const TYPE_OPTIONS = [
    { value: 'requests', label: 'Custom requests' },
    { value: 'consultations', label: 'Consultations' },
];

const ASSIGNED_OPTIONS = [
    { value: 'no', label: 'Unassigned' },
    { value: 'yes', label: 'Assigned' },
];

export default function AdminConsultations({
    consultations,
    filters,
    stats,
    statusOptions,
}: Props) {
    const rows = consultations.data;

    return (
        <>
            <Head title="Consultations" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Engagements"
                    title="Consultations"
                    description="Bookings and custom plan requests from clients."
                    actions={
                        <ExportButton path="/admin/consultations/export" />
                    }
                />

                <div className="grid gap-3 sm:grid-cols-3">
                    <StatCard
                        compact
                        label="All records"
                        value={stats.total}
                        icon={MessagesSquare}
                        tone="info"
                    />
                    <StatCard
                        compact
                        label="Custom requests to price"
                        value={stats.requests}
                        icon={Receipt}
                        tone="warning"
                    />
                    <StatCard
                        compact
                        label="Pending payment"
                        value={stats.pending}
                        icon={Clock}
                        tone="primary"
                    />
                </div>

                <DataTable
                    toolbar={
                        <ListFilters
                            url={consultationsRoute().url}
                            search={filters.search}
                            status={filters.status}
                            statusOptions={statusOptions}
                            extra={[
                                {
                                    param: 'type',
                                    value: filters.type,
                                    options: TYPE_OPTIONS,
                                    allLabel: 'All types',
                                },
                                {
                                    param: 'assigned',
                                    value: filters.assigned,
                                    options: ASSIGNED_OPTIONS,
                                    allLabel: 'Any expert',
                                },
                            ]}
                            searchPlaceholder="Search by client, reference or title…"
                        />
                    }
                    footer={
                        consultations.total > 0 ? (
                            <Pagination
                                links={consultations.links}
                                from={consultations.from}
                                to={consultations.to}
                                total={consultations.total}
                            />
                        ) : undefined
                    }
                >
                    <THead>
                        <tr>
                            <TH>Reference</TH>
                            <TH>Client</TH>
                            <TH>Type</TH>
                            <TH>Plan / Offer</TH>
                            <TH align="right">Amount</TH>
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
                                colSpan={8}
                                icon={MessagesSquare}
                                title="No consultations found"
                                description="Bookings and custom requests will be listed here."
                            />
                        ) : (
                            rows.map((c) => (
                                <TR
                                    key={c.uuid}
                                    href={consultation(c.uuid).url}
                                >
                                    <TD className="font-mono text-xs">
                                        {c.reference}
                                    </TD>
                                    <TD>
                                        <div className="font-medium">
                                            {c.name ?? '—'}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {c.email}
                                        </div>
                                    </TD>
                                    <TD>
                                        {c.unpriced ? (
                                            <Badge variant="warning">
                                                Request
                                            </Badge>
                                        ) : (
                                            <Badge variant="secondary">
                                                {c.is_custom
                                                    ? 'Custom'
                                                    : 'Consultation'}
                                            </Badge>
                                        )}
                                    </TD>
                                    <TD>{c.plan}</TD>
                                    <TD
                                        align="right"
                                        className="font-mono tabular-nums"
                                    >
                                        {c.unpriced ? '—' : c.amount}
                                    </TD>
                                    <TD>
                                        <ConsultationStatusBadge
                                            status={c.status}
                                            label={c.status_label}
                                        />
                                    </TD>
                                    <TD className="text-muted-foreground">
                                        {c.date ?? '—'}
                                    </TD>
                                    <TD align="right">
                                        <RowActions>
                                            <RowActionLink
                                                href={consultation(c.uuid)}
                                                icon={Eye}
                                                label={
                                                    c.unpriced
                                                        ? 'Review & price'
                                                        : 'View consultation'
                                                }
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
