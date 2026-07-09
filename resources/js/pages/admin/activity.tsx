import { Head, Link } from '@inertiajs/react';
import { ArrowRight, CalendarDays, History } from 'lucide-react';

import {
    DataTable,
    TBody,
    TD,
    TH,
    THead,
    TR,
    TableEmpty,
} from '@/components/data-table';
import { ListFilters } from '@/components/list-filters';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { activity as activityRoute } from '@/routes/admin';
import type { Paginated } from '@/types';

type BadgeVariant =
    | 'default'
    | 'secondary'
    | 'destructive'
    | 'outline'
    | 'success'
    | 'warning';

type ActivityRow = {
    id: number;
    log_name: string;
    description: string;
    type: string | null;
    message: string | null;
    from: string | null;
    to: string | null;
    causer: string;
    subject: string | null;
    subject_url: string | null;
    at: string;
    at_exact: string;
};

interface Props {
    activities: Paginated<ActivityRow>;
    logNames: { value: string; label: string }[];
    filters: { search: string; log_name: string };
    stats: { total: number; today: number };
}

const LOG_TONE: Record<string, BadgeVariant> = {
    payment: 'success',
    consultation: 'default',
    lead: 'warning',
    project: 'secondary',
    invoice: 'outline',
    payout: 'secondary',
};

export default function AdminActivity({
    activities,
    logNames,
    filters,
    stats,
}: Props) {
    const rows = activities.data;

    return (
        <>
            <Head title="Activity" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Audit trail"
                    title="Activity"
                    description="Every recorded admin action and system event across the platform."
                />

                <div className="grid gap-3 sm:grid-cols-2">
                    <StatCard
                        compact
                        label="Total events"
                        value={stats.total}
                        icon={History}
                        tone="info"
                    />
                    <StatCard
                        compact
                        label="Today"
                        value={stats.today}
                        icon={CalendarDays}
                        tone="primary"
                    />
                </div>

                <DataTable
                    toolbar={
                        <ListFilters
                            url={activityRoute().url}
                            search={filters.search}
                            extra={{
                                param: 'log_name',
                                value: filters.log_name,
                                options: logNames,
                                allLabel: 'All types',
                            }}
                            searchPlaceholder="Search the audit trail…"
                        />
                    }
                    footer={
                        activities.total > 0 ? (
                            <Pagination
                                links={activities.links}
                                from={activities.from}
                                to={activities.to}
                                total={activities.total}
                            />
                        ) : undefined
                    }
                >
                    <THead>
                        <tr>
                            <TH>When</TH>
                            <TH>Type</TH>
                            <TH>Event</TH>
                            <TH>Change</TH>
                            <TH>Subject</TH>
                            <TH>By</TH>
                        </tr>
                    </THead>
                    <TBody>
                        {rows.length === 0 ? (
                            <TableEmpty
                                colSpan={6}
                                icon={History}
                                title="No activity yet"
                                description="Admin actions and system events will appear here."
                            />
                        ) : (
                            rows.map((a) => (
                                <TR
                                    key={a.id}
                                    href={a.subject_url ?? undefined}
                                >
                                    <TD>
                                        <div className="whitespace-nowrap">
                                            {a.at}
                                        </div>
                                        <div className="text-xs whitespace-nowrap text-muted-foreground">
                                            {a.at_exact}
                                        </div>
                                    </TD>
                                    <TD>
                                        <Badge
                                            variant={
                                                LOG_TONE[a.log_name] ??
                                                'outline'
                                            }
                                        >
                                            {a.log_name}
                                        </Badge>
                                    </TD>
                                    <TD>
                                        <div className="font-medium">
                                            {a.description}
                                        </div>
                                        {a.message && (
                                            <div className="max-w-md truncate text-xs text-muted-foreground">
                                                {a.message}
                                            </div>
                                        )}
                                    </TD>
                                    <TD className="text-sm text-muted-foreground">
                                        {a.from && a.to ? (
                                            <span className="whitespace-nowrap">
                                                {a.from} → {a.to}
                                            </span>
                                        ) : (
                                            (a.to ?? '—')
                                        )}
                                    </TD>
                                    <TD>
                                        {a.subject ? (
                                            a.subject_url ? (
                                                <Link
                                                    href={a.subject_url}
                                                    className="inline-flex items-center gap-1 font-mono text-xs text-primary hover:underline"
                                                >
                                                    {a.subject}
                                                    <ArrowRight className="size-3" />
                                                </Link>
                                            ) : (
                                                <span className="font-mono text-xs">
                                                    {a.subject}
                                                </span>
                                            )
                                        ) : (
                                            '—'
                                        )}
                                    </TD>
                                    <TD className="whitespace-nowrap">
                                        {a.causer}
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
