import { Head, Link, router } from '@inertiajs/react';
import {
    Eye,
    Pencil,
    UserCheck,
    UserCog,
    UserPlus,
    Wallet,
} from 'lucide-react';

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
import {
    RowActionButton,
    RowActionLink,
    RowActions,
} from '@/components/row-actions';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { experts as expertsRoute } from '@/routes/admin';
import { create, edit, show } from '@/routes/admin/experts';
import { onboard } from '@/routes/admin/experts/payout';
import type { ExpertListItem, Paginated } from '@/types';

interface Props {
    experts: Paginated<ExpertListItem>;
    filters: { search: string };
    stats: { total: number; ready: number; active: number };
}

function PayoutBadge({
    enabled,
    hasAccount,
}: {
    enabled: boolean;
    hasAccount: boolean;
}) {
    if (enabled) {
        return <Badge variant="success">Ready</Badge>;
    }

    if (hasAccount) {
        return <Badge variant="secondary">Pending</Badge>;
    }

    return <Badge variant="outline">Not set up</Badge>;
}

export default function AdminExperts({ experts, filters, stats }: Props) {
    const rows = experts.data;

    return (
        <>
            <Head title="Experts" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Network"
                    title="Experts"
                    description="Your roster of specialists and their payout readiness."
                    actions={
                        <Button asChild>
                            <Link href={create()}>
                                <UserPlus className="size-4" /> Add expert
                            </Link>
                        </Button>
                    }
                />

                <div className="grid gap-3 sm:grid-cols-3">
                    <StatCard
                        compact
                        label="Experts"
                        value={stats.total}
                        icon={UserCog}
                        tone="info"
                    />
                    <StatCard
                        compact
                        label="Active"
                        value={stats.active}
                        icon={UserCheck}
                        tone="primary"
                    />
                    <StatCard
                        compact
                        label="Ready for payout"
                        value={stats.ready}
                        icon={Wallet}
                        tone="success"
                    />
                </div>

                <DataTable
                    toolbar={
                        <ListFilters
                            url={expertsRoute().url}
                            search={filters.search}
                            searchPlaceholder="Search by name, email or specialization…"
                        />
                    }
                    footer={
                        experts.total > 0 ? (
                            <Pagination
                                links={experts.links}
                                from={experts.from}
                                to={experts.to}
                                total={experts.total}
                            />
                        ) : undefined
                    }
                >
                    <THead>
                        <tr>
                            <TH>Name</TH>
                            <TH>Email</TH>
                            <TH>Specialization</TH>
                            <TH>Payout</TH>
                            <TH>Active</TH>
                            <TH>
                                <span className="sr-only">Actions</span>
                            </TH>
                        </tr>
                    </THead>
                    <TBody>
                        {rows.length === 0 ? (
                            <TableEmpty
                                colSpan={6}
                                icon={UserCog}
                                title="No experts found"
                                description="Add experts to assign engagements and send payouts."
                            />
                        ) : (
                            rows.map((ex) => (
                                <TR key={ex.id} href={show(ex.id).url}>
                                    <TD className="font-medium">
                                        <Link
                                            href={show(ex.id)}
                                            className="hover:text-primary hover:underline"
                                        >
                                            {ex.name}
                                        </Link>
                                    </TD>
                                    <TD className="text-muted-foreground">
                                        {ex.email ?? '—'}
                                    </TD>
                                    <TD>{ex.specialization ?? '—'}</TD>
                                    <TD>
                                        <PayoutBadge
                                            enabled={ex.stripe_payouts_enabled}
                                            hasAccount={ex.has_payout_account}
                                        />
                                    </TD>
                                    <TD>
                                        {ex.is_active ? (
                                            <Badge variant="success">
                                                Active
                                            </Badge>
                                        ) : (
                                            <Badge variant="secondary">
                                                Inactive
                                            </Badge>
                                        )}
                                    </TD>
                                    <TD align="right">
                                        <RowActions>
                                            <RowActionLink
                                                href={show(ex.id)}
                                                icon={Eye}
                                                label="View details"
                                            />
                                            <RowActionButton
                                                icon={Wallet}
                                                label={
                                                    ex.stripe_payouts_enabled
                                                        ? 'Re-open Stripe onboarding'
                                                        : 'Set up Stripe payouts'
                                                }
                                                onClick={() =>
                                                    router.post(
                                                        onboard(ex.id).url,
                                                    )
                                                }
                                            />
                                            <RowActionLink
                                                href={edit(ex.id)}
                                                icon={Pencil}
                                                label="Edit expert"
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
