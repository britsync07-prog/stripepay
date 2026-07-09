import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

import { Panel, panelLinkClass } from '@/components/panel';
import { leads } from '@/routes/admin';

import type { LeadsPipeline } from './types';

export function LeadsPipelinePanel({ pipeline }: { pipeline: LeadsPipeline }) {
    return (
        <Panel
            title="Leads pipeline"
            eyebrow="Sales"
            action={
                <Link href={leads()} className={`group/all ${panelLinkClass}`}>
                    Open board
                    <ArrowRight className="size-3.5 transition-transform group-hover/all:translate-x-0.5" />
                </Link>
            }
        >
            <div className="flex flex-wrap items-center justify-between gap-x-8 gap-y-5">
                <div className="flex flex-wrap gap-2">
                    {pipeline.stages.map((s) => (
                        <div
                            key={s.label}
                            className="flex items-baseline gap-2 rounded-xl border bg-muted/30 px-4 py-2.5"
                        >
                            <span className="font-mono text-xl font-bold tabular-nums">
                                {s.count}
                            </span>
                            <span className="text-sm text-muted-foreground">
                                {s.label}
                            </span>
                        </div>
                    ))}
                </div>
                <div className="flex gap-8">
                    <div>
                        <p className="text-xs text-muted-foreground">
                            Open pipeline
                        </p>
                        <p className="font-serif text-2xl font-semibold tabular-nums">
                            {pipeline.openValue}
                        </p>
                    </div>
                    <div>
                        <p className="text-xs text-muted-foreground">
                            Win rate
                        </p>
                        <p className="font-serif text-2xl font-semibold tabular-nums">
                            {pipeline.winRate !== null
                                ? `${pipeline.winRate}%`
                                : '—'}
                        </p>
                    </div>
                </div>
            </div>
        </Panel>
    );
}
