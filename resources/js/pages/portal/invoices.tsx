import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Eye, FileText } from 'lucide-react';

import { DataTable, TBody, TD, TH, THead, TR } from '@/components/data-table';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { RowActionLink, RowActions } from '@/components/row-actions';
import { Badge } from '@/components/ui/badge';
import { invoice as invoiceRoute } from '@/routes/portal';
import type { InvoiceListItem } from '@/types';

export default function PortalInvoices({
    invoices,
}: {
    invoices: InvoiceListItem[];
}) {
    return (
        <>
            <Head title="My Invoices" />
            <div className="animate-fade-up space-y-6">
                <PageHeader
                    eyebrow="Portal"
                    title="My Invoices"
                    description="Your invoices and their payment status."
                />

                {invoices.length === 0 ? (
                    <div className="rounded-2xl border border-dashed">
                        <EmptyState
                            icon={FileText}
                            title="No invoices yet"
                            description="Invoices for your engagements will appear here."
                        />
                    </div>
                ) : (
                    <>
                        {/* Mobile: cards */}
                        <div className="grid gap-3 md:hidden">
                            {invoices.map((i) => (
                                <Link
                                    key={i.uuid}
                                    href={invoiceRoute(i.uuid)}
                                    className="block surface-card p-4 transition-colors hover:bg-muted/40"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="truncate font-mono text-sm font-medium">
                                                {i.reference}
                                            </p>
                                            <p className="mt-0.5 text-xs text-muted-foreground">
                                                {i.issued_at ?? '—'}
                                            </p>
                                        </div>
                                        <Badge variant={i.status_tone}>
                                            {i.status_label}
                                        </Badge>
                                    </div>
                                    <div className="mt-3 flex items-center justify-between text-sm">
                                        <span className="font-mono tabular-nums">
                                            {i.total}
                                        </span>
                                        <span className="inline-flex items-center gap-1 text-xs font-medium text-primary">
                                            View invoice
                                            <ArrowRight className="size-3.5" />
                                        </span>
                                    </div>
                                </Link>
                            ))}
                        </div>

                        {/* Desktop: table */}
                        <DataTable className="hidden md:block">
                            <THead>
                                <tr>
                                    <TH>Invoice</TH>
                                    <TH align="right">Total</TH>
                                    <TH>Status</TH>
                                    <TH>Issued</TH>
                                    <TH>
                                        <span className="sr-only">Actions</span>
                                    </TH>
                                </tr>
                            </THead>
                            <TBody>
                                {invoices.map((i) => (
                                    <TR
                                        key={i.uuid}
                                        href={invoiceRoute(i.uuid).url}
                                    >
                                        <TD className="font-mono text-xs font-medium">
                                            {i.reference}
                                        </TD>
                                        <TD
                                            align="right"
                                            className="font-mono tabular-nums"
                                        >
                                            {i.total}
                                        </TD>
                                        <TD>
                                            <Badge variant={i.status_tone}>
                                                {i.status_label}
                                            </Badge>
                                        </TD>
                                        <TD className="text-muted-foreground">
                                            {i.issued_at ?? '—'}
                                        </TD>
                                        <TD align="right">
                                            <RowActions>
                                                <RowActionLink
                                                    href={invoiceRoute(i.uuid)}
                                                    icon={Eye}
                                                    label="View invoice"
                                                />
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
