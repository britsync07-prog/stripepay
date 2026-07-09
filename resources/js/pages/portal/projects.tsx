import { Head, Link } from '@inertiajs/react';
import { ArrowRight, FolderKanban } from 'lucide-react';

import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { project as projectRoute } from '@/routes/portal';
import type { ClientProjectListItem } from '@/types';

export default function PortalProjects({
    projects,
}: {
    projects: ClientProjectListItem[];
}) {
    return (
        <>
            <Head title="My Projects" />
            <div className="animate-fade-up space-y-6">
                <PageHeader
                    eyebrow="Portal"
                    title="My Projects"
                    description="Track delivery progress on your engagements."
                />

                {projects.length === 0 ? (
                    <div className="rounded-2xl border border-dashed">
                        <EmptyState
                            icon={FolderKanban}
                            title="No projects yet"
                            description="Once an engagement kicks off, your project will appear here."
                        />
                    </div>
                ) : (
                    <div className="grid gap-3 sm:grid-cols-2">
                        {projects.map((p) => (
                            <Link
                                key={p.uuid}
                                href={projectRoute(p.uuid)}
                                className="block surface-card p-5 transition-colors hover:bg-muted/40"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="truncate font-medium">
                                            {p.title}
                                        </p>
                                        <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                                            {p.reference}
                                        </p>
                                    </div>
                                    <Badge variant={p.status_tone}>
                                        {p.status_label}
                                    </Badge>
                                </div>

                                <div className="mt-4 flex items-center gap-2">
                                    <div
                                        role="progressbar"
                                        aria-valuenow={p.completion_percentage}
                                        aria-valuemin={0}
                                        aria-valuemax={100}
                                        aria-label={`${p.completion_percentage}% of milestones completed`}
                                        className="h-1.5 flex-1 overflow-hidden rounded-full bg-muted"
                                    >
                                        <div
                                            className="h-full rounded-full bg-primary transition-all"
                                            style={{
                                                width: `${p.completion_percentage}%`,
                                            }}
                                        />
                                    </div>
                                    <span className="font-mono text-xs text-muted-foreground tabular-nums">
                                        {p.completion_percentage}%
                                    </span>
                                </div>

                                <div className="mt-3 flex items-center justify-between text-xs">
                                    <span className="text-muted-foreground">
                                        {p.expert
                                            ? `Consultant: ${p.expert}`
                                            : 'Consultant to be assigned'}
                                    </span>
                                    <span className="inline-flex items-center gap-1 font-medium text-primary">
                                        View project
                                        <ArrowRight className="size-3.5" />
                                    </span>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
