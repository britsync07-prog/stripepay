import { Head, Link } from '@inertiajs/react';
import { Eye, FileText, FolderKanban, Receipt } from 'lucide-react';

import { ConsultationStatusBadge } from '@/components/consultation-status-badge';
import { DataTable, TBody, TD, TH, THead, TR } from '@/components/data-table';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { RowActionLink, RowActions } from '@/components/row-actions';
import { Button } from '@/components/ui/button';
import { consultation, invoice, project } from '@/routes/portal';
import type { ClientConsultation } from '@/types';

export default function Consultations({
    consultations,
}: {
    consultations: ClientConsultation[];
}) {
    return (
        <>
            <Head title="My Consultations" />
            <div className="animate-fade-up space-y-6">
                <PageHeader
                    eyebrow="Portal"
                    title="My Consultations"
                    description="Your engagements and their status."
                />

                {consultations.length === 0 ? (
                    <div className="rounded-2xl border border-dashed">
                        <EmptyState
                            icon={Receipt}
                            title="No consultations yet"
                            description="Pick a plan to start your first engagement."
                            action={
                                <Button asChild>
                                    <Link href="/">Browse plans</Link>
                                </Button>
                            }
                        />
                    </div>
                ) : (
                    <>
                        {/* Mobile: cards */}
                        <div className="grid gap-3 md:hidden">
                            {consultations.map((c) => (
                                <Link
                                    key={c.uuid}
                                    href={consultation(c.uuid)}
                                    className="block surface-card p-4 transition-colors hover:bg-muted/40"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">
                                                {c.plan}
                                            </p>
                                            <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                                                {c.reference}
                                            </p>
                                        </div>
                                        <ConsultationStatusBadge
                                            status={c.status}
                                            label={c.status_label}
                                        />
                                    </div>
                                    <div className="mt-3 flex items-center justify-between text-sm">
                                        <span className="font-mono tabular-nums">
                                            {c.amount}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {c.date ?? '—'}
                                        </span>
                                    </div>
                                </Link>
                            ))}
                        </div>

                        {/* Desktop: table */}
                        <DataTable className="hidden md:block">
                            <THead>
                                <tr>
                                    <TH>Reference</TH>
                                    <TH>Plan</TH>
                                    <TH align="right">Amount</TH>
                                    <TH>Status</TH>
                                    <TH>Date</TH>
                                    <TH>
                                        <span className="sr-only">Actions</span>
                                    </TH>
                                </tr>
                            </THead>
                            <TBody>
                                {consultations.map((c) => (
                                    <TR
                                        key={c.uuid}
                                        href={consultation(c.uuid).url}
                                    >
                                        <TD className="font-mono text-xs">
                                            {c.reference}
                                        </TD>
                                        <TD className="font-medium">
                                            {c.plan}
                                        </TD>
                                        <TD
                                            align="right"
                                            className="font-mono tabular-nums"
                                        >
                                            {c.amount}
                                        </TD>
                                        <TD>
                                            <ConsultationStatusBadge
                                                status={c.status}
                                                label={c.status_label}
                                            />
                                        </TD>
                                        <TD className="text-muted-foreground">
                                            {c.date ?? '—'}
                                        </TD>
                                        <TD align="right">
                                            <RowActions>
                                                <RowActionLink
                                                    href={consultation(c.uuid)}
                                                    icon={Eye}
                                                    label="View consultation"
                                                />
                                                {c.project_uuid && (
                                                    <RowActionLink
                                                        href={project(
                                                            c.project_uuid,
                                                        )}
                                                        icon={FolderKanban}
                                                        label="View project"
                                                    />
                                                )}
                                                {c.invoice_uuid && (
                                                    <RowActionLink
                                                        href={invoice(
                                                            c.invoice_uuid,
                                                        )}
                                                        icon={FileText}
                                                        label="View invoice"
                                                    />
                                                )}
                                            </RowActions>
                                        </TD>
                                    </TR>
                                ))}
                            </TBody>
                        </DataTable>
                    </>
                )}
            </div>
        </>
    );
}
