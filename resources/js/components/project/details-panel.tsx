import { Panel } from '@/components/panel';
import type { ProjectDetail } from '@/types';

function MetaRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-baseline justify-between gap-3 py-2">
            <span className="text-sm text-muted-foreground">{label}</span>
            <span className="text-right text-sm font-medium">{value}</span>
        </div>
    );
}

export function DetailsPanel({ project }: { project: ProjectDetail }) {
    return (
        <Panel eyebrow="Details" title="Project">
            <div className="divide-y">
                <MetaRow label="Client" value={project.client ?? '—'} />
                <MetaRow label="Consultant" value={project.expert ?? '—'} />
                <MetaRow
                    label="Engagement"
                    value={project.consultation_reference ?? '—'}
                />
                <MetaRow label="Created" value={project.created_at ?? '—'} />
            </div>
        </Panel>
    );
}
