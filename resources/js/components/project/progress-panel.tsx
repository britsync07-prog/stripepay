import { Panel } from '@/components/panel';

export function ProgressPanel({ percent }: { percent: number }) {
    return (
        <Panel
            eyebrow="Progress"
            title="Delivery"
            description={`${percent}% of milestones completed`}
        >
            <div className="h-2.5 w-full overflow-hidden rounded-full bg-muted">
                <div
                    className="h-full rounded-full bg-primary transition-all"
                    style={{ width: `${percent}%` }}
                />
            </div>
        </Panel>
    );
}
