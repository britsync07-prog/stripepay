import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Banknote,
    CalendarDays,
    CreditCard,
    Eye,
    Mail,
    MessagesSquare,
    Pencil,
    UserPlus,
} from 'lucide-react';
import { useState } from 'react';

import { ClientFormDialog } from '@/components/clients/client-form-dialog';
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
import { ListFilters } from '@/components/list-filters';
import { Pagination } from '@/components/pagination';
import { Reveal } from '@/components/reveal';
import { RowActionLink, RowActions } from '@/components/row-actions';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { consultation } from '@/routes/admin';
import { clients as clientsIndex } from '@/routes/admin';
import { lead as clientLeadRoute, show } from '@/routes/admin/clients';
import type { Client, Paginated, Payment, SelectOption } from '@/types';

interface Props {
    client: Client;
    payments: Paginated<Payment>;
    filters: { search: string; status: string };
    countries: SelectOption[];
    stats: { spent: string; payments: number; consultations: number };
    statusOptions: { value: string; label: string }[];
}

export default function ShowClientDetails({
    client,
    payments,
    filters,
    countries,
    stats,
    statusOptions,
}: Props) {
    const [editOpen, setEditOpen] = useState(false);
    const rows = payments.data;

    const initials =
        client.name
            .split(/\s+/)
            .map((w) => w[0])
            .filter(Boolean)
            .slice(0, 2)
            .join('')
            .toUpperCase() || '?';

    return (
        <>
            <Head title={client.name} />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <Link
                    href={clientsIndex()}
                    className="inline-flex w-fit items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft className="size-4" /> All clients
                </Link>

                <Reveal>
                    <div className="overflow-hidden surface-card">
                        <div className="flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-center gap-4">
                                <span className="flex size-16 shrink-0 items-center justify-center rounded-xl bg-muted font-serif text-xl font-semibold text-foreground ring-1 ring-border ring-inset">
                                    {initials}
                                </span>
                                <div className="min-w-0">
                                    <p className="text-[0.7rem] font-medium tracking-wide text-primary/80 uppercase">
                                        Client
                                    </p>
                                    <h1 className="truncate font-serif text-2xl font-semibold">
                                        {client.name}
                                    </h1>
                                    <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
                                        <span className="inline-flex items-center gap-1.5">
                                            <Mail className="size-3.5" />
                                            {client.email}
                                        </span>
                                        {client.joined && (
                                            <span className="inline-flex items-center gap-1.5">
                                                <CalendarDays className="size-3.5" />
                                                Joined {client.joined}
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </div>

                            <div className="flex shrink-0 flex-wrap items-center gap-2">
                                <Button
                                    variant="outline"
                                    onClick={() => setEditOpen(true)}
                                >
                                    <Pencil className="size-4" />
                                    Edit
                                </Button>
                                <Button
                                    onClick={() =>
                                        router.post(
                                            clientLeadRoute(client.id).url,
                                        )
                                    }
                                >
                                    <UserPlus className="size-4" />
                                    Start a new lead
                                </Button>
                            </div>
                        </div>
                    </div>
                </Reveal>

                {/* Stats */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <StatCard
                        label="Total spent"
                        value={stats.spent}
                        icon={Banknote}
                        tone="primary"
                    />
                    <StatCard
                        label="Payments"
                        value={stats.payments}
                        icon={CreditCard}
                        tone="info"
                    />
                    <StatCard
                        label="Consultations"
                        value={stats.consultations}
                        icon={MessagesSquare}
                        tone="success"
                    />
                </div>

                {/* Payment history */}
                <div className="flex flex-col gap-4">
                    <h2 className="font-serif text-lg font-semibold">
                        Payment history
                    </h2>

                    <DataTable
                        toolbar={
                            <ListFilters
                                url={show(client.id).url}
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
                                <TH>Status</TH>
                                <TH>Reference</TH>
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
                                    title="No payments yet"
                                    description="This client's transactions will appear here."
                                />
                            ) : (
                                rows.map((p, i) => (
                                    <TR key={i}>
                                        <TD className="font-medium">
                                            {p.plan}
                                        </TD>
                                        <TD
                                            align="right"
                                            className="font-mono tabular-nums"
                                        >
                                            {p.amount}
                                        </TD>
                                        <TD className="capitalize">
                                            {p.gateway}
                                        </TD>
                                        <TD>
                                            <ConsultationStatusBadge
                                                status={p.status}
                                                label={p.status}
                                            />
                                        </TD>
                                        <TD className="font-mono text-xs text-muted-foreground">
                                            {p.reference ?? '—'}
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
            </div>

            <ClientFormDialog
                open={editOpen}
                onOpenChange={setEditOpen}
                countries={countries}
                client={client}
            />
        </>
    );
}
