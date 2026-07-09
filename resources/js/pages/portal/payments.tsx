import { Head, Link } from '@inertiajs/react';
import { ArrowRight, CreditCard, Eye } from 'lucide-react';

import { ConsultationStatusBadge } from '@/components/consultation-status-badge';
import { DataTable, TBody, TD, TH, THead, TR } from '@/components/data-table';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { RowActionLink, RowActions } from '@/components/row-actions';
import { consultation } from '@/routes/portal';
import type { Payment } from '@/types';

export default function Payments({ payments }: { payments: Payment[] }) {
    return (
        <>
            <Head title="Payments" />
            <div className="animate-fade-up space-y-6">
                <PageHeader
                    eyebrow="Portal"
                    title="Payments"
                    description="Your transaction history."
                />

                {payments.length === 0 ? (
                    <div className="rounded-2xl border border-dashed">
                        <EmptyState
                            icon={CreditCard}
                            title="No payments yet"
                            description="Your settled transactions will appear here."
                        />
                    </div>
                ) : (
                    <>
                        {/* Mobile: cards */}
                        <div className="grid gap-3 md:hidden">
                            {payments.map((p, i) => {
                                const card = (
                                    <>
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="truncate font-medium">
                                                    {p.plan}
                                                </p>
                                                <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                                                    {p.reference ?? '—'}
                                                </p>
                                            </div>
                                            <ConsultationStatusBadge
                                                status={p.status}
                                                label={p.status}
                                            />
                                        </div>
                                        <div className="mt-3 flex items-center justify-between text-sm">
                                            <span className="font-mono tabular-nums">
                                                {p.amount}
                                            </span>
                                            <span className="text-muted-foreground capitalize">
                                                {p.gateway}
                                            </span>
                                        </div>
                                        <div className="mt-1 flex items-center justify-between text-xs">
                                            {p.consultation ? (
                                                <span className="inline-flex items-center gap-1 font-medium text-primary">
                                                    View consultation
                                                    <ArrowRight className="size-3.5" />
                                                </span>
                                            ) : (
                                                <span />
                                            )}
                                            <span className="text-muted-foreground">
                                                {p.date ?? '—'}
                                            </span>
                                        </div>
                                    </>
                                );

                                return p.consultation ? (
                                    <Link
                                        key={i}
                                        href={consultation(p.consultation)}
                                        className="block surface-card p-4 transition-colors hover:bg-muted/40"
                                    >
                                        {card}
                                    </Link>
                                ) : (
                                    <div key={i} className="surface-card p-4">
                                        {card}
                                    </div>
                                );
                            })}
                        </div>

                        {/* Desktop: table */}
                        <DataTable className="hidden md:block">
                            <THead>
                                <tr>
                                    <TH>Plan</TH>
                                    <TH align="right">Amount</TH>
                                    <TH>Gateway</TH>
                                    <TH>Reference</TH>
                                    <TH>Status</TH>
                                    <TH>Date</TH>
                                    <TH>
                                        <span className="sr-only">Actions</span>
                                    </TH>
                                </tr>
                            </THead>
                            <TBody>
                                {payments.map((p, i) => (
                                    <TR
                                        key={i}
                                        href={
                                            p.consultation
                                                ? consultation(p.consultation)
                                                      .url
                                                : undefined
                                        }
                                    >
                                        <TD className="font-medium">
                                            {p.consultation ? (
                                                <Link
                                                    href={consultation(
                                                        p.consultation,
                                                    )}
                                                    className="hover:text-primary hover:underline"
                                                >
                                                    {p.plan}
                                                </Link>
                                            ) : (
                                                p.plan
                                            )}
                                        </TD>
                                        <TD
                                            align="right"
                                            className="font-mono tabular-nums"
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
                                                label={p.status}
                                            />
                                        </TD>
                                        <TD className="text-muted-foreground">
                                            {p.date ?? '—'}
                                        </TD>
                                        <TD align="right">
                                            {p.consultation && (
                                                <RowActions>
                                                    <RowActionLink
                                                        href={consultation(
                                                            p.consultation,
                                                        )}
                                                        icon={Eye}
                                                        label="View consultation"
                                                    />
                                                </RowActions>
                                            )}
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
