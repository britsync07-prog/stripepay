import { Head, Link, router } from '@inertiajs/react';
import {
    Activity,
    CheckCircle2,
    Eye,
    FolderKanban,
    Rocket,
    Sparkles,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

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
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useAiEnabled } from '@/hooks/use-ai-enabled';
import { projects as projectsRoute } from '@/routes/admin';
import { assessAll as assessAllRoute, show } from '@/routes/admin/projects';
import type { Paginated, ProjectListItem } from '@/types';

interface Props {
    projects: Paginated<ProjectListItem>;
    filters: { search: string; status: string };
    statusOptions: { value: string; label: string }[];
    stats: { total: number; active: number; completed: number };
}

function ProgressBar({ value }: { value: number }) {
    return (
        <div className="flex items-center gap-2">
            <div className="h-1.5 w-20 overflow-hidden rounded-full bg-muted">
                <div
                    className="h-full rounded-full bg-primary transition-all"
                    style={{ width: `${value}%` }}
                />
            </div>
            <span className="font-mono text-xs text-muted-foreground tabular-nums">
                {value}%
            </span>
        </div>
    );
}

export default function AdminProjects({
    projects,
    filters,
    statusOptions,
    stats,
}: Props) {
    const rows = projects.data;
    const aiEnabled = useAiEnabled();
    const [assessing, setAssessing] = useState(false);
    const poll = useRef<ReturnType<typeof setInterval>>(undefined);

    useEffect(() => () => clearInterval(poll.current), []);

    const assessWithAi = () => {
        router.post(
            assessAllRoute().url,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    // Verdicts land as staggered jobs finish — refresh a few
                    // times so the health chips fill in, then stop.
                    setAssessing(true);
                    clearInterval(poll.current);
                    let pulls = 0;
                    poll.current = setInterval(() => {
                        pulls += 1;
                        router.reload({ only: ['projects', 'stats'] });

                        if (pulls >= 6) {
                            clearInterval(poll.current);
                            setAssessing(false);
                        }
                    }, 4000);
                },
            },
        );
    };

    return (
        <>
            <Head title="Projects" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Delivery"
                    title="Projects"
                    description="Every engagement, tracked through milestone-based delivery."
                    actions={
                        aiEnabled ? (
                            <Button
                                variant="outline"
                                onClick={assessWithAi}
                                disabled={assessing}
                                title="Assess the delivery health of every active project"
                            >
                                {assessing ? (
                                    <>
                                        <Spinner className="size-4" />{' '}
                                        Assessing…
                                    </>
                                ) : (
                                    <>
                                        <Sparkles className="size-4" /> Assess
                                        health
                                    </>
                                )}
                            </Button>
                        ) : undefined
                    }
                />

                <div className="grid gap-3 sm:grid-cols-3">
                    <StatCard
                        compact
                        label="Projects"
                        value={stats.total}
                        icon={FolderKanban}
                        tone="info"
                    />
                    <StatCard
                        compact
                        label="Active"
                        value={stats.active}
                        icon={Rocket}
                        tone="primary"
                    />
                    <StatCard
                        compact
                        label="Completed"
                        value={stats.completed}
                        icon={CheckCircle2}
                        tone="success"
                    />
                </div>

                <DataTable
                    toolbar={
                        <ListFilters
                            url={projectsRoute().url}
                            search={filters.search}
                            status={filters.status}
                            statusOptions={statusOptions}
                            searchPlaceholder="Search by reference, title, client or expert…"
                        />
                    }
                    footer={
                        projects.total > 0 ? (
                            <Pagination
                                links={projects.links}
                                from={projects.from}
                                to={projects.to}
                                total={projects.total}
                            />
                        ) : undefined
                    }
                >
                    <THead>
                        <tr>
                            <TH>Project</TH>
                            <TH>Client</TH>
                            <TH>Consultant</TH>
                            <TH>Progress</TH>
                            <TH>Status</TH>
                            <TH>Health</TH>
                            <TH>
                                <span className="sr-only">Actions</span>
                            </TH>
                        </tr>
                    </THead>
                    <TBody>
                        {rows.length === 0 ? (
                            <TableEmpty
                                colSpan={7}
                                icon={FolderKanban}
                                title="No projects yet"
                                description="Create a project to start tracking milestone-based delivery."
                            />
                        ) : (
                            rows.map((p) => (
                                <TR key={p.uuid} href={show(p.uuid).url}>
                                    <TD className="font-medium">
                                        <Link
                                            href={show(p.uuid)}
                                            className="hover:text-primary hover:underline"
                                        >
                                            {p.title}
                                        </Link>
                                        <div className="font-mono text-xs text-muted-foreground">
                                            {p.reference} · {p.milestones_count}{' '}
                                            milestone
                                            {p.milestones_count === 1
                                                ? ''
                                                : 's'}
                                        </div>
                                    </TD>
                                    <TD className="text-muted-foreground">
                                        {p.client ?? '—'}
                                    </TD>
                                    <TD className="text-muted-foreground">
                                        {p.expert ?? '—'}
                                    </TD>
                                    <TD>
                                        <ProgressBar
                                            value={p.completion_percentage}
                                        />
                                    </TD>
                                    <TD>
                                        <Badge variant={p.status_tone}>
                                            {p.status_label}
                                        </Badge>
                                    </TD>
                                    <TD>
                                        {p.health && p.health_tone ? (
                                            <Badge
                                                variant={p.health_tone}
                                                className="gap-1"
                                            >
                                                <Activity className="size-2.5" />
                                                {p.health_label}
                                            </Badge>
                                        ) : (
                                            <span className="text-xs text-muted-foreground">
                                                —
                                            </span>
                                        )}
                                    </TD>
                                    <TD align="right">
                                        <RowActions>
                                            <RowActionLink
                                                href={show(p.uuid)}
                                                icon={Eye}
                                                label="View project"
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
