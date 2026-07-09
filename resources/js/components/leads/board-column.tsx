import { useDroppable } from '@dnd-kit/core';

import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

import { LeadCard } from './lead-card';
import type { Column, Lead } from './types';

export function BoardColumn({
    column,
    onFollowUp,
}: {
    column: Column;
    onFollowUp: (lead: Lead) => void;
}) {
    const { setNodeRef, isOver } = useDroppable({ id: column.stage });

    return (
        <div className="flex w-72 shrink-0 flex-col">
            <div className="mb-2 flex items-center justify-between px-1">
                <div className="flex items-center gap-2">
                    <Badge variant={column.tone}>{column.label}</Badge>
                    <span className="font-mono text-xs text-muted-foreground tabular-nums">
                        {column.count}
                    </span>
                </div>
            </div>

            <div
                ref={setNodeRef}
                className={cn(
                    'flex min-h-32 flex-1 flex-col gap-2 rounded-2xl border border-dashed p-2 transition-colors',
                    isOver
                        ? 'border-primary/60 bg-primary/5'
                        : 'border-muted-foreground/30 bg-muted/50',
                )}
            >
                {column.leads.map((lead) => (
                    <LeadCard
                        key={lead.uuid}
                        lead={lead}
                        onFollowUp={onFollowUp}
                    />
                ))}
                {column.leads.length === 0 && (
                    <p className="px-2 py-6 text-center text-xs text-muted-foreground">
                        No leads
                    </p>
                )}
                {column.capped && (
                    <p className="px-2 pb-1 text-center text-[10px] text-muted-foreground">
                        Showing the most recent {column.leads.length} of{' '}
                        {column.count}
                    </p>
                )}
            </div>
        </div>
    );
}
