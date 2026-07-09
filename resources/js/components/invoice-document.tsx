import { usePage } from '@inertiajs/react';
import { CheckCircle2, Undo2 } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { InvoiceDetail } from '@/types';

/**
 * The on-screen invoice "paper" — a refined, Stripe-style document shared by
 * the admin and client invoice pages so both render identically.
 */
export function InvoiceDocument({ invoice }: { invoice: InvoiceDetail }) {
    const company = usePage().props.company;
    const appName = company.name;
    const refunded = invoice.status === 'refunded';

    return (
        <article className="overflow-hidden surface-elevated">
            <div className="h-px bg-gradient-to-r from-transparent via-primary/70 to-transparent" />

            {/* Header — brand + invoice ref */}
            <header className="flex flex-wrap items-start justify-between gap-4 px-8 pt-8 sm:px-12 sm:pt-12">
                <div className="flex items-center gap-3">
                    <img
                        src="/assets/images/logoBA-ink.png"
                        alt={appName}
                        className="h-9 w-auto"
                    />
                </div>
                <div className="text-right">
                    <p className="font-mono text-[0.7rem] font-medium tracking-[0.22em] text-muted-foreground uppercase">
                        Invoice
                    </p>
                    <p className="mt-1 font-mono text-sm font-medium">
                        {invoice.reference}
                    </p>
                </div>
            </header>

            {/* Hero — the amount + paid state (Stripe signature) */}
            <div className="mt-8 flex flex-wrap items-end justify-between gap-4 border-y bg-gradient-to-b from-muted/30 to-transparent px-8 py-6 sm:px-12">
                <div>
                    <p className="text-[0.7rem] font-medium tracking-[0.18em] text-muted-foreground uppercase">
                        {refunded ? 'Amount refunded' : 'Amount paid'}
                    </p>
                    <p className="mt-1 font-serif text-4xl font-semibold tracking-tight tabular-nums">
                        {invoice.total}
                    </p>
                </div>
                <Badge
                    variant="outline"
                    className={cn(
                        'gap-1.5 rounded-full border-transparent px-3 py-1.5 font-semibold ring-1 [&>svg]:size-3.5',
                        refunded
                            ? 'bg-destructive/10 text-destructive ring-destructive/20'
                            : 'bg-success/10 text-success ring-success/20',
                    )}
                >
                    {refunded ? <Undo2 /> : <CheckCircle2 />}
                    {refunded
                        ? 'Refunded'
                        : `Paid${invoice.paid_at ? ` on ${invoice.paid_at}` : ''}`}
                </Badge>
            </div>

            {/* Parties + dates */}
            <div className="grid gap-6 px-8 py-8 sm:grid-cols-2 sm:px-12">
                <div>
                    <p className="text-[0.7rem] font-medium tracking-[0.18em] text-muted-foreground uppercase">
                        Billed to
                    </p>
                    <p className="mt-2 text-sm font-semibold">
                        {invoice.client ?? '—'}
                    </p>
                    {invoice.consultation_reference && (
                        <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                            {invoice.consultation_reference}
                        </p>
                    )}
                </div>
                <dl className="space-y-1.5 text-sm sm:text-right">
                    <div className="flex justify-between gap-8 sm:justify-end">
                        <dt className="text-muted-foreground">Issued</dt>
                        <dd className="font-medium sm:w-32">
                            {invoice.issued_at ?? '—'}
                        </dd>
                    </div>
                    <div className="flex justify-between gap-8 sm:justify-end">
                        <dt className="text-muted-foreground">From</dt>
                        <dd className="font-medium sm:w-32">{appName}</dd>
                    </div>
                    {company.vat && (
                        <div className="flex justify-between gap-8 sm:justify-end">
                            <dt className="text-muted-foreground">Tax ID</dt>
                            <dd className="font-medium sm:w-32">
                                {company.vat}
                            </dd>
                        </div>
                    )}
                </dl>
            </div>

            {/* Line items */}
            <div className="px-8 sm:px-12">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-[0.7rem] tracking-[0.12em] text-muted-foreground uppercase">
                            <th className="py-2 pr-3 text-left font-medium">
                                Description
                            </th>
                            <th className="px-3 py-2 text-right font-medium">
                                Qty
                            </th>
                            <th className="px-3 py-2 text-right font-medium">
                                Unit
                            </th>
                            <th className="py-2 pl-3 text-right font-medium">
                                Amount
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-border/60">
                        {invoice.items.map((item) => (
                            <tr key={item.id}>
                                <td className="py-3 pr-3 font-medium">
                                    {item.description}
                                </td>
                                <td className="px-3 py-3 text-right font-mono text-muted-foreground tabular-nums">
                                    {item.quantity}
                                </td>
                                <td className="px-3 py-3 text-right font-mono text-muted-foreground tabular-nums">
                                    {item.unit_amount}
                                </td>
                                <td className="py-3 pl-3 text-right font-mono font-medium tabular-nums">
                                    {item.amount}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Totals */}
            <div className="flex justify-end px-8 pt-6 sm:px-12">
                <dl className="w-full max-w-[16rem] space-y-2 text-sm">
                    <div className="flex justify-between">
                        <dt className="text-muted-foreground">Subtotal</dt>
                        <dd className="font-mono tabular-nums">
                            {invoice.subtotal}
                        </dd>
                    </div>
                    <div className="flex justify-between">
                        <dt className="text-muted-foreground">
                            Tax ({invoice.tax_rate}%)
                        </dt>
                        <dd className="font-mono tabular-nums">
                            {invoice.tax_amount}
                        </dd>
                    </div>
                    <div className="mt-1 flex items-baseline justify-between border-t border-border pt-3">
                        <dt className="font-serif text-base font-semibold">
                            Total
                        </dt>
                        <dd className="text-gold-gradient font-mono text-lg font-semibold tabular-nums">
                            {invoice.total}
                        </dd>
                    </div>
                </dl>
            </div>

            {/* Footer */}
            <footer className="mt-8 border-t bg-muted/15 px-8 py-5 sm:px-12">
                {invoice.notes ? (
                    <p className="text-sm whitespace-pre-line text-muted-foreground">
                        {invoice.notes}
                    </p>
                ) : (
                    <p className="text-xs whitespace-pre-line text-muted-foreground">
                        {company.footer ??
                            `Thank you for working with ${appName}. Questions about this invoice? Reply to your engagement thread and our team will help.`}
                    </p>
                )}
            </footer>
        </article>
    );
}
