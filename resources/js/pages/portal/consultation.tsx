import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    Check,
    Download,
    FileText,
    FolderKanban,
    UserRound,
} from 'lucide-react';

import { ConsultationStatusBadge } from '@/components/consultation-status-badge';
import { DataTable, TBody, TD, TH, THead, TR } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { Panel } from '@/components/panel';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import {
    consultations,
    invoice as invoiceRoute,
    project as projectRoute,
} from '@/routes/portal';
import { pdf as invoicePdf } from '@/routes/portal/invoice';
import type { ClientConsultation, Payment } from '@/types';

type Related = {
    project: { uuid: string; reference: string | null } | null;
    invoice: { uuid: string; reference: string | null } | null;
};
type Step = { value: string; label: string };

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase())
        .join('');
}

function Detail({ label, value }: { label: string; value: string | null }) {
    return (
        <div>
            <dt className="text-xs tracking-wide text-muted-foreground uppercase">
                {label}
            </dt>
            <dd className="mt-0.5 text-sm font-medium">{value || '—'}</dd>
        </div>
    );
}

function Consultant({ name }: { name: string | null }) {
    return (
        <div className="flex items-center gap-3">
            <span
                className={cn(
                    'flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                    name
                        ? 'bg-primary/10 text-primary'
                        : 'bg-muted text-muted-foreground',
                )}
            >
                {name ? initials(name) : <UserRound className="size-4" />}
            </span>
            <div>
                <p className="text-[11px] font-medium tracking-[0.08em] text-muted-foreground uppercase">
                    Your consultant
                </p>
                <p className="text-sm font-medium">
                    {name ?? 'Being assigned'}
                </p>
            </div>
        </div>
    );
}

function ProgressTracker({
    current,
    steps,
}: {
    current: string;
    steps: Step[];
}) {
    const cancelled = current === 'cancelled';
    const idx = cancelled
        ? -1
        : Math.max(
              0,
              steps.findIndex((s) => s.value === current),
          );

    return (
        <ol className="relative">
            {steps.map((step, i) => {
                const done = !cancelled && i < idx;
                const active = !cancelled && i === idx;
                const connect = i < steps.length - 1 || cancelled;

                return (
                    <li key={step.value} className="flex gap-3 pb-4">
                        <div className="relative flex flex-col items-center">
                            <span
                                className={cn(
                                    'z-10 flex size-6 shrink-0 items-center justify-center rounded-full border',
                                    done &&
                                        'border-primary bg-primary text-primary-foreground',
                                    active &&
                                        'border-primary text-primary ring-4 ring-primary/15',
                                    !done &&
                                        !active &&
                                        'border-border bg-card text-muted-foreground',
                                )}
                            >
                                {done ? (
                                    <Check className="size-3.5" />
                                ) : (
                                    <span className="size-1.5 rounded-full bg-current" />
                                )}
                            </span>
                            {connect && (
                                <span
                                    className={cn(
                                        'mt-1 w-px flex-1',
                                        done ? 'bg-primary' : 'bg-border',
                                    )}
                                />
                            )}
                        </div>
                        <div className="-mt-0.5 pb-1">
                            <p
                                className={cn(
                                    'text-sm font-medium',
                                    !done && !active && 'text-muted-foreground',
                                )}
                            >
                                {step.label}
                            </p>
                            {active && (
                                <p className="text-xs text-muted-foreground">
                                    Current stage
                                </p>
                            )}
                        </div>
                    </li>
                );
            })}

            {cancelled && (
                <li className="flex gap-3">
                    <span className="z-10 flex size-6 shrink-0 items-center justify-center rounded-full border border-destructive bg-destructive text-white ring-4 ring-destructive/15">
                        <Ban className="size-3.5" />
                    </span>
                    <div className="-mt-0.5">
                        <p className="text-sm font-medium text-destructive">
                            Cancelled
                        </p>
                        <p className="text-xs text-muted-foreground">
                            Current stage
                        </p>
                    </div>
                </li>
            )}
        </ol>
    );
}

export default function ConsultationDetails({
    consultation,
    payments,
    workStatus,
    workStatusSteps,
    related,
}: {
    consultation: ClientConsultation;
    payments: Payment[];
    workStatus: string;
    workStatusSteps: Step[];
    related: Related;
}) {
    return (
        <>
            <Head title={`Consultation ${consultation.reference}`} />
            <div className="animate-fade-up space-y-6">
                <Link
                    href={consultations()}
                    className="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft className="size-4" /> All consultations
                </Link>

                <PageHeader
                    eyebrow="Engagement"
                    title={consultation.plan}
                    description={
                        <span className="font-mono text-xs">
                            {consultation.reference}
                        </span>
                    }
                    actions={
                        <ConsultationStatusBadge
                            status={consultation.status}
                            label={consultation.status_label}
                        />
                    }
                />

                {consultation.description && (
                    <p className="max-w-3xl text-sm leading-relaxed text-muted-foreground">
                        {consultation.description}
                    </p>
                )}

                <div className="grid items-start gap-6 lg:grid-cols-3">
                    <div className="order-2 space-y-6 lg:order-1 lg:col-span-2">
                        <Panel
                            title="Progress"
                            action={
                                <ConsultationStatusBadge status={workStatus} />
                            }
                        >
                            <ProgressTracker
                                current={workStatus}
                                steps={workStatusSteps}
                            />
                        </Panel>

                        <Panel title="Details">
                            <dl className="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3">
                                <Detail
                                    label="Amount"
                                    value={consultation.amount}
                                />
                                <Detail
                                    label="Purchased"
                                    value={consultation.created_at}
                                />
                                <Detail
                                    label="Paid on"
                                    value={consultation.paid_at}
                                />
                                <Detail
                                    label="Name"
                                    value={consultation.name}
                                />
                                <Detail
                                    label="Email"
                                    value={consultation.email}
                                />
                                <Detail
                                    label="Phone"
                                    value={consultation.phone}
                                />
                                <Detail
                                    label="Country"
                                    value={consultation.country}
                                />
                            </dl>
                        </Panel>

                        {consultation.features.length > 0 && (
                            <Panel title="What's included">
                                <ul className="grid gap-2.5 sm:grid-cols-2">
                                    {consultation.features.map((feature) => (
                                        <li
                                            key={feature}
                                            className="flex items-start gap-2 text-sm"
                                        >
                                            <Check className="mt-0.5 size-4 shrink-0 text-primary" />
                                            <span>{feature}</span>
                                        </li>
                                    ))}
                                </ul>
                            </Panel>
                        )}

                        <Panel title="Payments">
                            {payments.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No payments recorded.
                                </p>
                            ) : (
                                <>
                                    <div className="grid gap-3 md:hidden">
                                        {payments.map((p, i) => (
                                            <div
                                                key={p.reference ?? i}
                                                className="rounded-xl border bg-muted/30 p-3"
                                            >
                                                <div className="flex items-center justify-between gap-3">
                                                    <span className="font-mono font-medium tabular-nums">
                                                        {p.amount}
                                                    </span>
                                                    <ConsultationStatusBadge
                                                        status={p.status}
                                                    />
                                                </div>
                                                <div className="mt-2 flex items-center justify-between text-xs text-muted-foreground">
                                                    <span className="capitalize">
                                                        {p.gateway}
                                                    </span>
                                                    <span>{p.date ?? '—'}</span>
                                                </div>
                                                <p className="mt-1 font-mono text-xs text-muted-foreground">
                                                    {p.reference ?? '—'}
                                                </p>
                                            </div>
                                        ))}
                                    </div>

                                    <DataTable bare className="hidden md:block">
                                        <THead>
                                            <tr>
                                                <TH>Amount</TH>
                                                <TH>Gateway</TH>
                                                <TH>Reference</TH>
                                                <TH>Status</TH>
                                                <TH>Date</TH>
                                            </tr>
                                        </THead>
                                        <TBody>
                                            {payments.map((p, i) => (
                                                <TR key={p.reference ?? i}>
                                                    <TD
                                                        as="th"
                                                        className="font-mono font-medium tabular-nums"
                                                    >
                                                        {p.amount}
                                                    </TD>
                                                    <TD className="capitalize">
                                                        {p.gateway}
                                                    </TD>
                                                    <TD className="font-mono text-xs text-muted-foreground">
                                                        {p.reference ?? '—'}
                                                    </TD>
                                                    <TD>
                                                        <ConsultationStatusBadge
                                                            status={p.status}
                                                        />
                                                    </TD>
                                                    <TD className="text-muted-foreground">
                                                        {p.date ?? '—'}
                                                    </TD>
                                                </TR>
                                            ))}
                                        </TBody>
                                    </DataTable>
                                </>
                            )}
                        </Panel>
                    </div>

                    <div className="order-1 space-y-6 lg:order-2">
                        <Panel>
                            <Consultant name={consultation.assigned_expert} />
                        </Panel>

                        {(related.project || related.invoice) && (
                            <Panel title="Related">
                                <div className="flex flex-col gap-2">
                                    {related.project && (
                                        <Button
                                            asChild
                                            variant="outline"
                                            className="justify-start"
                                        >
                                            <Link
                                                href={projectRoute(
                                                    related.project.uuid,
                                                )}
                                            >
                                                <FolderKanban className="size-4" />
                                                View project
                                            </Link>
                                        </Button>
                                    )}
                                    {related.invoice && (
                                        <>
                                            <Button
                                                asChild
                                                variant="outline"
                                                className="justify-start"
                                            >
                                                <Link
                                                    href={invoiceRoute(
                                                        related.invoice.uuid,
                                                    )}
                                                >
                                                    <FileText className="size-4" />
                                                    View invoice
                                                </Link>
                                            </Button>
                                            <Button
                                                asChild
                                                variant="outline"
                                                className="justify-start"
                                            >
                                                <a
                                                    href={
                                                        invoicePdf(
                                                            related.invoice
                                                                .uuid,
                                                        ).url
                                                    }
                                                >
                                                    <Download className="size-4" />
                                                    Download invoice
                                                </a>
                                            </Button>
                                        </>
                                    )}
                                </div>
                            </Panel>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
