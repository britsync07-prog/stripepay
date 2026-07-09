import { Head, Link, router } from '@inertiajs/react';
import { DollarSign, Eye, Plus, Upload, UserPlus, Users } from 'lucide-react';
import { useState } from 'react';

import { ClientFormDialog } from '@/components/clients/client-form-dialog';
import { ImportClientsDialog } from '@/components/clients/import-clients-dialog';
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
import {
    RowActionButton,
    RowActionLink,
    RowActions,
} from '@/components/row-actions';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { clients as clientsRoute } from '@/routes/admin';
import {
    exportMethod,
    lead as clientLeadRoute,
    show,
} from '@/routes/admin/clients';
import type { Client, Paginated, SelectOption } from '@/types';

interface Props {
    clients: Paginated<Client>;
    filters: { search: string };
    countries: SelectOption[];
    stats: { total: number; spent: string };
}

export default function AdminClients({
    clients,
    filters,
    countries,
    stats,
}: Props) {
    const rows = clients.data;
    const [addOpen, setAddOpen] = useState(false);
    const [importOpen, setImportOpen] = useState(false);

    return (
        <>
            <Head title="Clients" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Directory"
                    title="Clients"
                    description="Everyone who has booked a consultation."
                    actions={
                        <div className="flex flex-wrap items-center gap-2">
                            <Button
                                variant="outline"
                                onClick={() => setImportOpen(true)}
                            >
                                <Upload className="size-4" /> Import
                            </Button>
                            <ExportButton path={exportMethod().url} />
                            <Button onClick={() => setAddOpen(true)}>
                                <Plus className="size-4" /> Add client
                            </Button>
                        </div>
                    }
                />

                <div className="grid gap-3 sm:grid-cols-2">
                    <StatCard
                        compact
                        label="Clients"
                        value={stats.total}
                        icon={Users}
                        tone="info"
                    />
                    <StatCard
                        compact
                        label="Total received from clients"
                        value={stats.spent}
                        icon={DollarSign}
                        tone="primary"
                    />
                </div>

                <DataTable
                    toolbar={
                        <ListFilters
                            url={clientsRoute().url}
                            search={filters.search}
                            searchPlaceholder="Search by name or email…"
                        />
                    }
                    footer={
                        clients.total > 0 ? (
                            <Pagination
                                links={clients.links}
                                from={clients.from}
                                to={clients.to}
                                total={clients.total}
                            />
                        ) : undefined
                    }
                >
                    <THead>
                        <tr>
                            <TH>Name</TH>
                            <TH>Email</TH>
                            <TH align="right">Consultations</TH>
                            <TH align="right">Total spent</TH>
                            <TH>Joined</TH>
                            <TH>
                                <span className="sr-only">Actions</span>
                            </TH>
                        </tr>
                    </THead>
                    <TBody>
                        {rows.length === 0 ? (
                            <TableEmpty
                                colSpan={6}
                                icon={Users}
                                title="No clients found"
                                description="Clients appear here after their first consultation."
                            />
                        ) : (
                            rows.map((c) => (
                                <TR key={c.id} href={show(c.id).url}>
                                    <TD className="font-medium">
                                        <Link
                                            href={show(c.id)}
                                            className="hover:text-primary hover:underline"
                                        >
                                            {c.name}
                                        </Link>
                                    </TD>
                                    <TD className="text-muted-foreground">
                                        {c.email}
                                    </TD>
                                    <TD align="right" className="tabular-nums">
                                        {c.consultations_count}
                                    </TD>
                                    <TD
                                        align="right"
                                        className="font-mono tabular-nums"
                                    >
                                        {c.spent}
                                    </TD>
                                    <TD className="text-muted-foreground">
                                        {c.joined ?? '—'}
                                    </TD>
                                    <TD align="right">
                                        <RowActions>
                                            <RowActionButton
                                                icon={UserPlus}
                                                label="Start a new lead"
                                                onClick={() =>
                                                    router.post(
                                                        clientLeadRoute(c.id)
                                                            .url,
                                                    )
                                                }
                                            />
                                            <RowActionLink
                                                href={show(c.id)}
                                                icon={Eye}
                                                label="View details"
                                            />
                                        </RowActions>
                                    </TD>
                                </TR>
                            ))
                        )}
                    </TBody>
                </DataTable>
            </div>

            <ClientFormDialog
                open={addOpen}
                onOpenChange={setAddOpen}
                countries={countries}
            />
            <ImportClientsDialog
                open={importOpen}
                onOpenChange={setImportOpen}
            />
        </>
    );
}
