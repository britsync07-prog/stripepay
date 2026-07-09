import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { PageHeader } from '@/components/page-header';
import { DetailsPanel } from '@/components/project/details-panel';
import { DocumentsPanel } from '@/components/project/documents-panel';
import { ProjectHealthCard } from '@/components/project/health-card';
import { MilestonesPanel } from '@/components/project/milestones-panel';
import { ProgressPanel } from '@/components/project/progress-panel';
import { UpdateDraftCard } from '@/components/project/update-draft-card';
import { Badge } from '@/components/ui/badge';
import { useAiEnabled } from '@/hooks/use-ai-enabled';
import { projects as projectsIndex } from '@/routes/admin';
import type { MediaFile, ProjectDetail, ProjectHealth } from '@/types';

export default function ProjectShow({
    project,
    health,
    documents,
    consultationAttachments,
}: {
    project: ProjectDetail;
    health: ProjectHealth;
    documents: MediaFile[];
    consultationAttachments: MediaFile[];
}) {
    const aiEnabled = useAiEnabled();
    const count = project.milestones.length;

    return (
        <>
            <Head title={project.title} />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <Link
                    href={projectsIndex()}
                    className="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft className="size-4" /> All projects
                </Link>

                <PageHeader
                    eyebrow={project.reference}
                    title={project.title}
                    description={`${project.completion_percentage}% complete · ${count} milestone${count === 1 ? '' : 's'}`}
                    actions={
                        <Badge variant={project.status_tone}>
                            {project.status_label}
                        </Badge>
                    }
                />

                <div className="grid items-start gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <ProgressPanel
                            percent={project.completion_percentage}
                        />
                        <MilestonesPanel
                            projectUuid={project.uuid}
                            milestones={project.milestones}
                        />
                        <DocumentsPanel
                            project={project}
                            documents={documents}
                            consultationAttachments={consultationAttachments}
                        />
                    </div>

                    <aside className="space-y-6 lg:sticky lg:top-6">
                        <DetailsPanel project={project} />
                        {aiEnabled && (
                            <>
                                <ProjectHealthCard
                                    uuid={project.uuid}
                                    initial={health}
                                />
                                <UpdateDraftCard uuid={project.uuid} />
                            </>
                        )}
                    </aside>
                </div>
            </div>
        </>
    );
}
