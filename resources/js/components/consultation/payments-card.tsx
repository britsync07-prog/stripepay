import { Deferred } from '@inertiajs/react';

import { Section } from '@/components/consultation/section';
import { ConsultationStatusBadge } from '@/components/consultation-status-badge';
import { DataTable, TBody, TD, TH, THead, TR } from '@/components/data-table';
import { Skeleton } from '@/components/ui/skeleton';
import type { Payment } from '@/types';

function PaymentsTable({ payments }: { payments: Payment[] }) {
    if (payments.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No payments recorded.
            </p>
        );
    }

    return (
        <DataTable bare>
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
                        <TD className="capitalize">{p.gateway}</TD>
                        <TD className="font-mono text-xs text-muted-foreground">
                            {p.reference ?? '—'}
                        </TD>
                        <TD>
                            <ConsultationStatusBadge status={p.status} />
                        </TD>
                        <TD className="text-muted-foreground">
                            {p.date ?? '—'}
                        </TD>
                    </TR>
                ))}
            </TBody>
        </DataTable>
    );
}

function PaymentsSkeleton() {
    return (
        <div className="space-y-2.5">
            {Array.from({ length: 2 }).map((_, i) => (
                <div key={i} className="flex items-center gap-4">
                    <Skeleton className="h-4 w-20" />
                    <Skeleton className="h-4 w-16" />
                    <Skeleton className="h-4 flex-1" />
                    <Skeleton className="h-5 w-16 rounded-full" />
                </div>
            ))}
        </div>
    );
}

export function PaymentsCard({ payments }: { payments?: Payment[] }) {
    return (
        <Section title="Payments">
            <Deferred data="payments" fallback={<PaymentsSkeleton />}>
                <PaymentsTable payments={payments ?? []} />
            </Deferred>
        </Section>
    );
}
