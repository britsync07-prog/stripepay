import { useDraggable } from '@dnd-kit/core';
import { CSS } from '@dnd-kit/utilities';
import { Link } from '@inertiajs/react';
import {
    Building2,
    CalendarClock,
    ExternalLink,
    Gauge,
    GripVertical,
} from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

import { FollowUp } from './follow-up';
import type { Lead } from './types';

export function LeadCard({
    lead,
    onFollowUp,
}: {
    lead: Lead;
    onFollowUp: (lead: Lead) => void;
}) {
    // A won lead is closed — it can't be dragged out of its column.
    const locked = lead.stage === 'won';
    const { attributes, listeners, setNodeRef, transform, isDragging } =
        useDraggable({
            id: lead.uuid,
            data: { stage: lead.stage },
            disabled: locked,
        });

    return (
        <div
            ref={setNodeRef}
            style={{ transform: CSS.Translate.toString(transform) }}
            className={cn(
                'group rounded-xl border bg-card p-3 shadow-card edge-highlight transition-shadow',
                isDragging
                    ? 'z-20 opacity-90 shadow-elevated'
                    : 'hover:shadow-elevated',
                lead.follow_up_overdue && 'ring-1 ring-destructive/30',
            )}
        >
            <div className="flex items-start gap-2">
                {!locked && (
                    <button
                        type="button"
                        aria-label="Drag to another stage"
                        className="mt-0.5 -ml-1 flex size-6 shrink-0 cursor-grab touch-none items-center justify-center rounded text-muted-foreground/50 transition-colors hover:bg-muted hover:text-foreground active:cursor-grabbing"
                        {...attributes}
                        {...listeners}
                    >
                        <GripVertical className="size-4" />
                    </button>
                )}

                <div className="min-w-0 flex-1">
                    <div className="flex items-center justify-between gap-2">
                        <p className="truncate font-medium">
                            {lead.name ?? 'Unnamed lead'}
                        </p>
                        <div className="flex shrink-0 items-center gap-1">
                            {lead.score !== null && lead.score_band_tone && (
                                <Badge
                                    variant={lead.score_band_tone}
                                    className="gap-0.5 px-1.5 font-mono text-[10px] tabular-nums"
                                    title={`AI lead score: ${lead.score}/100 (${lead.score_band})`}
                                >
                                    <Gauge className="size-2.5" />
                                    {lead.score}
                                </Badge>
                            )}
                            {lead.source === 'manual' && (
                                <Badge
                                    variant="outline"
                                    className="text-[10px]"
                                >
                                    Manual
                                </Badge>
                            )}
                        </div>
                    </div>

                    {lead.company && (
                        <p className="mt-0.5 flex items-center gap-1 truncate text-xs text-muted-foreground">
                            <Building2 className="size-3 shrink-0" />
                            {lead.company}
                        </p>
                    )}

                    <div className="mt-2 flex items-center justify-between gap-2">
                        <span className="font-mono text-xs tabular-nums">
                            {lead.unpriced ? (
                                <span className="text-muted-foreground">
                                    Unpriced
                                </span>
                            ) : (
                                lead.amount
                            )}
                        </span>
                        <FollowUp lead={lead} />
                    </div>

                    <div className="mt-2 flex items-center gap-1 border-t pt-2">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="h-7 px-2 text-xs"
                            onClick={() => onFollowUp(lead)}
                        >
                            <CalendarClock className="size-3" />
                            Follow-up
                        </Button>
                        <Button
                            asChild
                            variant="ghost"
                            size="sm"
                            className="ml-auto h-7 px-2 text-xs"
                        >
                            <Link href={lead.consultation_url}>
                                Open
                                <ExternalLink className="size-3" />
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}
