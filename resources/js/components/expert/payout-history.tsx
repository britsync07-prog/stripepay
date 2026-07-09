import { Banknote, Paperclip } from 'lucide-react';

import { ConsultationStatusBadge } from '@/components/consultation-status-badge';
import {
    DataTable,
    TableEmpty,
    TBody,
    TD,
    TH,
    THead,
    TR,
} from '@/components/data-table';
import { ListFilters } from '@/components/list-filters';
import { Pagination } from '@/components/pagination';
import { show } from '@/routes/admin/experts';
import type { Paginated, Payout, SelectOption } from '@/types';

export function PayoutHistory({
    expertId,
    payouts,
    filters,
    statusOptions,
}: {
    expertId: number;
    payouts: Paginated<Payout>;
    filters: { status: string };
    statusOptions: SelectOption[];
}) {
    const rows = payouts.data;

    return (
        <div className="flex flex-col gap-4">
            <h2 className="font-serif text-lg font-semibold">Payout history</h2>

            <DataTable
                toolbar={
                    <ListFilters
                        url={show(expertId).url}
                        searchable={false}
                        status={filters.status}
                        statusOptions={statusOptions}
                    />
                }
                footer={
                    payouts.total > 0 ? (
                        <Pagination
                            links={payouts.links}
                            from={payouts.from}
                            to={payouts.to}
                            total={payouts.total}
                        />
                    ) : undefined
                }
            >
                <THead>
                    <tr>
                        <TH align="right">Amount</TH>
                        <TH>Method</TH>
                        <TH>Status</TH>
                        <TH>Reference</TH>
                        <TH>Date</TH>
                        <TH>Proof</TH>
                    </tr>
                </THead>
                <TBody>
                    {rows.length === 0 ? (
                        <TableEmpty
                            colSpan={6}
                            icon={Banknote}
                            title="No payouts yet"
                            description="Payouts sent to this expert will appear here."
                        />
                    ) : (
                        rows.map((p) => (
                            <TR key={p.id}>
                                <TD
                                    align="right"
                                    className="font-mono tabular-nums"
                                >
                                    {p.amount}
                                </TD>
                                <TD>{p.method_label}</TD>
                                <TD>
                                    <ConsultationStatusBadge
                                        status={p.status}
                                        label={p.status_label}
                                    />
                                </TD>
                                <TD className="font-mono text-xs text-muted-foreground">
                                    {p.reference ?? '—'}
                                </TD>
                                <TD className="text-muted-foreground">
                                    {p.paid_at ?? p.created_at ?? '—'}
                                </TD>
                                <TD>
                                    {p.proof_url ? (
                                        <a
                                            href={p.proof_url}
                                            target="_blank"
                                            rel="noopener"
                                            className="inline-flex items-center gap-1 text-primary hover:underline"
                                        >
                                            <Paperclip className="size-3.5" />{' '}
                                            View
                                        </a>
                                    ) : (
                                        '—'
                                    )}
                                </TD>
                            </TR>
                        ))
                    )}
                </TBody>
            </DataTable>
        </div>
    );
}
