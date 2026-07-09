import type { QuotationLineItem } from '@/types';

/**
 * The quotation's line items. Two layouts share the same pre-formatted amounts:
 * - `table` — the full Description / Qty / Unit / Total grid for the wide
 *   quotation document.
 * - `list` — a stacked, readable layout for narrow columns (the checkout
 *   order summary), where a 4-column table would be cramped.
 */
export function QuotationLines({
    items,
    variant = 'table',
}: {
    items: QuotationLineItem[];
    variant?: 'table' | 'list';
}) {
    if (variant === 'list') {
        return (
            <ul className="divide-y divide-border/60">
                {items.map((item, i) => (
                    <li
                        key={`${item.description}-${i}`}
                        className="flex items-start justify-between gap-3 py-2.5"
                    >
                        <div className="min-w-0">
                            <p className="text-sm font-medium">
                                {item.description}
                            </p>
                            <p className="mt-0.5 font-mono text-xs text-muted-foreground tabular-nums">
                                {item.quantity} × {item.unitPrice}
                            </p>
                        </div>
                        <span className="shrink-0 font-mono text-sm font-semibold tabular-nums">
                            {item.total}
                        </span>
                    </li>
                ))}
            </ul>
        );
    }

    return (
        <table className="w-full text-sm">
            <thead>
                <tr className="border-b text-microcaps text-muted-foreground">
                    <th className="py-2 text-left font-medium">Description</th>
                    <th className="py-2 text-right font-medium">Qty</th>
                    <th className="py-2 text-right font-medium">Unit</th>
                    <th className="py-2 text-right font-medium">Total</th>
                </tr>
            </thead>
            <tbody>
                {items.map((item, i) => (
                    <tr
                        key={`${item.description}-${i}`}
                        className="border-b border-border/60 last:border-0"
                    >
                        <td className="py-2.5 pr-2 font-medium">
                            {item.description}
                        </td>
                        <td className="py-2.5 text-right font-mono text-muted-foreground tabular-nums">
                            {item.quantity}
                        </td>
                        <td className="py-2.5 text-right font-mono text-muted-foreground tabular-nums">
                            {item.unitPrice}
                        </td>
                        <td className="py-2.5 pl-2 text-right font-mono font-semibold tabular-nums">
                            {item.total}
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}
