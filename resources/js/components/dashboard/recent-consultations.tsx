import { Link } from '@inertiajs/react';
import { ArrowRight, Receipt } from 'lucide-react';

import { ConsultationStatusBadge } from '@/components/consultation-status-badge';
import { EmptyState } from '@/components/empty-state';
import { Panel, panelLinkClass } from '@/components/panel';
import { Badge } from '@/components/ui/badge';
import { consultation, consultations } from '@/routes/admin';
import type { Consultation } from '@/types';

export function RecentConsultations({ recent }: { recent: Consultation[] }) {
    return (
        <Panel
            title="Recent consultations"
            className="h-full"
            action={
                <Link
                    href={consultations()}
                    className={`group/all ${panelLinkClass}`}
                >
                    View all
                    <ArrowRight className="size-3.5 transition-transform group-hover/all:translate-x-0.5" />
                </Link>
            }
        >
            <div className="divide-y divide-border/70">
                {recent.length === 0 ? (
                    <EmptyState
                        icon={Receipt}
                        title="No consultations yet"
                        description="New bookings will show up here as soon as they come in."
                        className="py-12"
                    />
                ) : (
                    recent.map((c) => (
                        <Link
                            key={c.uuid}
                            href={consultation(c.uuid)}
                            className="group/row flex items-center justify-between gap-3 py-3 transition-colors hover:text-primary"
                        >
                            <div className="min-w-0">
                                <p className="flex items-center gap-2 text-sm font-medium">
                                    <span className="truncate">{c.plan}</span>
                                    {c.unpriced && (
                                        <Badge
                                            variant="outline"
                                            className="shrink-0"
                                        >
                                            Needs pricing
                                        </Badge>
                                    )}
                                </p>
                                <p className="font-mono text-xs text-muted-foreground">
                                    {c.reference}
                                </p>
                            </div>
                            <div className="flex shrink-0 items-center gap-3">
                                <span className="font-mono text-sm tabular-nums">
                                    {c.unpriced ? '—' : c.amount}
                                </span>
                                <ConsultationStatusBadge
                                    status={c.status}
                                    label={c.status_label}
                                />
                                <ArrowRight className="size-4 text-muted-foreground transition-transform group-hover/row:translate-x-0.5 group-hover/row:text-foreground" />
                            </div>
                        </Link>
                    ))
                )}
            </div>
        </Panel>
    );
}
