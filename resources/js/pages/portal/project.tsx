import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    CheckCircle2,
    Circle,
    CircleDot,
} from 'lucide-react';

import { AttachmentList } from '@/components/attachments';
import { PageHeader } from '@/components/page-header';
import { Panel } from '@/components/panel';
import { Badge } from '@/components/ui/badge';
import { projects as projectsIndex } from '@/routes/portal';
import { show as downloadDocument } from '@/routes/portal/project/documents';
import type { MediaFile, Milestone, ProjectDetail } from '@/types';

function MilestoneIcon({ status }: { status: string }) {
    if (status === 'completed') {
        return <CheckCircle2 className="size-5 text-success" />;
    }

    if (status === 'in_progress') {
        return <CircleDot className="size-5 text-primary" />;
    }

    return <Circle className="size-5 text-muted-foreground" />;
}

function TimelineItem({ milestone }: { milestone: Milestone }) {
    return (
        <li className="flex items-start gap-3 px-5 py-4">
            <span className="mt-0.5">
                <MilestoneIcon status={milestone.status} />
            </span>
            <div className="min-w-0 flex-1">
                <div className="flex items-center justify-between gap-3">
                    <p className="font-medium">{milestone.title}</p>
                    <span className="shrink-0 text-xs text-muted-foreground">
                        {milestone.status_label}
                    </span>
                </div>
                {milestone.description && (
                    <p className="mt-0.5 text-sm text-muted-foreground">
                        {milestone.description}
                    </p>
                )}
                {milestone.due_date && (
                    <p className="mt-1 inline-flex items-center gap-1 text-xs text-muted-foreground">
                        <CalendarDays className="size-3.5" /> Due{' '}
                        {milestone.due_date}
                    </p>
                )}
            </div>
        </li>
    );
}

export default function PortalProject({
    project,
    documents,
}: {
    project: ProjectDetail;
    documents: MediaFile[];
}) {
    return (
        <>
            <Head title={project.title} />
            <div className="animate-fade-up space-y-6">
                <Link
                    href={projectsIndex()}
                    className="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft className="size-4" /> All projects
                </Link>

                <PageHeader
                    eyebrow={project.reference}
                    title={project.title}
                    description={
                        project.expert
                            ? `Your consultant: ${project.expert}`
                            : 'Consultant to be assigned'
                    }
                    actions={
                        <Badge variant={project.status_tone}>
                            {project.status_label}
                        </Badge>
                    }
                />

                <Panel
                    eyebrow="Progress"
                    title="Delivery"
                    description={`${project.completion_percentage}% of milestones completed`}
                >
                    <div className="h-2.5 w-full overflow-hidden rounded-full bg-muted">
                        <div
                            className="h-full rounded-full bg-primary transition-all"
                            style={{
                                width: `${project.completion_percentage}%`,
                            }}
                        />
                    </div>
                </Panel>

                {documents.length > 0 && (
                    <Panel eyebrow="Documents" title="Files & deliverables">
                        <AttachmentList
                            items={documents}
                            downloadUrl={(id) =>
                                downloadDocument([project.uuid, id]).url
                            }
                        />
                    </Panel>
                )}

                <Panel flush eyebrow="Timeline" title="Milestones">
                    <ul className="divide-y">
                        {project.milestones.length === 0 ? (
                            <li className="px-5 py-8 text-center text-sm text-muted-foreground">
                                Milestones will appear here as your project is
                                planned.
                            </li>
                        ) : (
                            project.milestones.map((m) => (
                                <TimelineItem key={m.id} milestone={m} />
                            ))
                        )}
                    </ul>
                </Panel>
            </div>
        </>
    );
}
