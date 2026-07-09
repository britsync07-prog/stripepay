import { Building2, Mail, MapPin, Phone, User } from 'lucide-react';
import type { ReactNode } from 'react';

import { ConsultationStatusBadge } from '@/components/consultation-status-badge';
import { Badge } from '@/components/ui/badge';
import type { Consultation } from '@/types';

function StatusChip({
    label,
    children,
}: {
    label: string;
    children: ReactNode;
}) {
    return (
        <div className="flex items-center gap-1.5">
            <span className="text-[10px] font-medium tracking-wide text-muted-foreground uppercase">
                {label}
            </span>
            {children}
        </div>
    );
}

function IdentityItem({
    icon: Icon,
    children,
    href,
}: {
    icon: typeof User;
    children: ReactNode;
    href?: string;
}) {
    const inner = (
        <>
            <Icon className="size-3.5 shrink-0 text-muted-foreground" />
            <span className="truncate">{children}</span>
        </>
    );

    return href ? (
        <a
            href={href}
            className="inline-flex min-w-0 items-center gap-1.5 text-muted-foreground transition-colors hover:text-foreground"
        >
            {inner}
        </a>
    ) : (
        <span className="inline-flex min-w-0 items-center gap-1.5 text-muted-foreground">
            {inner}
        </span>
    );
}

export function ConsultationHero({
    consultation,
}: {
    consultation: Consultation;
}) {
    return (
        <section className="relative overflow-hidden surface-card">
            <div
                aria-hidden
                className="pointer-events-none absolute -top-20 -right-16 size-48 rounded-full bg-primary/10 blur-3xl"
            />
            <div className="relative flex flex-col gap-6 p-5 sm:p-6 lg:flex-row lg:items-start lg:justify-between">
                <div className="min-w-0 space-y-3">
                    <div>
                        <span className="inline-flex items-center gap-2 text-eyebrow text-primary">
                            <span className="h-px w-4 bg-primary/50" />
                            Consultation · {consultation.reference}
                        </span>
                        <h1 className="mt-1 font-serif text-2xl font-semibold tracking-tight text-balance">
                            {consultation.plan}
                        </h1>
                    </div>

                    <div className="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm">
                        <span className="inline-flex min-w-0 items-center gap-1.5 font-medium text-foreground">
                            <User className="size-3.5 shrink-0 text-primary" />
                            <span className="truncate">
                                {consultation.name ?? 'Unknown client'}
                            </span>
                        </span>
                        {consultation.email && (
                            <IdentityItem
                                icon={Mail}
                                href={`mailto:${consultation.email}`}
                            >
                                {consultation.email}
                            </IdentityItem>
                        )}
                        {consultation.phone && (
                            <IdentityItem
                                icon={Phone}
                                href={`tel:${consultation.phone}`}
                            >
                                {consultation.phone}
                            </IdentityItem>
                        )}
                        {consultation.country && (
                            <IdentityItem icon={MapPin}>
                                {consultation.country}
                            </IdentityItem>
                        )}
                        {consultation.is_custom && (
                            <IdentityItem icon={Building2}>
                                Custom request
                            </IdentityItem>
                        )}
                    </div>
                </div>

                <div className="flex shrink-0 flex-col gap-4 lg:items-end">
                    <div className="lg:text-right">
                        <span className="text-[10px] font-medium tracking-wide text-muted-foreground uppercase">
                            {consultation.unpriced ? 'Price' : 'Amount'}
                        </span>
                        <div className="text-gold-gradient font-mono text-2xl font-semibold tabular-nums">
                            {consultation.unpriced
                                ? 'Not set'
                                : consultation.amount}
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-x-4 gap-y-2 lg:justify-end">
                        <StatusChip label="Payment">
                            <ConsultationStatusBadge
                                status={consultation.status}
                                label={consultation.status_label}
                            />
                        </StatusChip>
                        <StatusChip label="Work">
                            <ConsultationStatusBadge
                                status={consultation.work_status}
                                label={consultation.work_status_label}
                            />
                        </StatusChip>
                        {consultation.is_lead &&
                            consultation.lead_stage_tone && (
                                <StatusChip label="Lead">
                                    <Badge
                                        variant={consultation.lead_stage_tone}
                                    >
                                        {consultation.lead_stage_label}
                                    </Badge>
                                </StatusChip>
                            )}
                    </div>
                </div>
            </div>
        </section>
    );
}
