import { Plus, Trash2 } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCurrency } from '@/hooks/use-currency';

export interface QuotationLineInput {
    description: string;
    quantity: number;
    unit_amount: string;
}

/**
 * The shared scope-of-services + line-items + validity editor. Controlled — the
 * parent owns the state — so it works inside the on-request quotation builder and
 * the reusable template form alike.
 */
export function QuotationFields({
    scope,
    onScopeChange,
    lines,
    onLinesChange,
    validDays,
    onValidDaysChange,
    delivery,
    onDeliveryChange,
    lineError,
}: {
    scope: string[];
    onScopeChange: (v: string[]) => void;
    lines: QuotationLineInput[];
    onLinesChange: (v: QuotationLineInput[]) => void;
    validDays: number;
    onValidDaysChange: (v: number) => void;
    delivery: string;
    onDeliveryChange: (v: string) => void;
    lineError?: string;
}) {
    const currency = useCurrency();

    // Client-side preview only — the server recomputes from the same lines.
    const total = lines.reduce(
        (sum, l) =>
            sum + (Number(l.unit_amount) || 0) * (Number(l.quantity) || 0),
        0,
    );

    const setScope = (i: number, value: string) =>
        onScopeChange(scope.map((s, idx) => (idx === i ? value : s)));
    const addScope = () => onScopeChange([...scope, '']);
    const removeScope = (i: number) =>
        onScopeChange(scope.filter((_, idx) => idx !== i));

    const setLine = (i: number, patch: Partial<QuotationLineInput>) =>
        onLinesChange(
            lines.map((l, idx) => (idx === i ? { ...l, ...patch } : l)),
        );
    const addLine = () =>
        onLinesChange([
            ...lines,
            { description: '', quantity: 1, unit_amount: '' },
        ]);
    const removeLine = (i: number) =>
        onLinesChange(lines.filter((_, idx) => idx !== i));

    return (
        <div className="grid gap-5">
            {/* Scope of services */}
            <div className="grid gap-1.5">
                <Label className="text-xs">Scope of services</Label>
                <div className="grid gap-2">
                    {scope.map((item, i) => (
                        <div key={i} className="flex gap-2">
                            <Input
                                value={item}
                                onChange={(e) => setScope(i, e.target.value)}
                                placeholder="e.g. Strategic Discovery Session (90 minutes)"
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={() => removeScope(i)}
                                disabled={scope.length === 1}
                                aria-label="Remove scope item"
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        </div>
                    ))}
                </div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={addScope}
                    className="justify-self-start"
                >
                    <Plus className="mr-1.5 size-3.5" /> Add scope item
                </Button>
            </div>

            {/* Line items */}
            <div className="grid gap-1.5">
                <Label className="text-xs">Line items</Label>
                <div className="grid gap-2">
                    <div className="hidden grid-cols-[1fr_72px_110px_36px] gap-2 px-1 text-microcaps text-muted-foreground sm:grid">
                        <span>Description</span>
                        <span>Qty</span>
                        <span>Unit ({currency.code})</span>
                        <span />
                    </div>
                    {lines.map((line, i) => (
                        <div
                            key={i}
                            className="grid grid-cols-[1fr_72px_110px_36px] gap-2"
                        >
                            <Input
                                value={line.description}
                                onChange={(e) =>
                                    setLine(i, { description: e.target.value })
                                }
                                placeholder="Strategic Business Assessment"
                            />
                            <Input
                                type="number"
                                min="1"
                                value={line.quantity}
                                onChange={(e) =>
                                    setLine(i, {
                                        quantity: Number(e.target.value),
                                    })
                                }
                            />
                            <Input
                                type="number"
                                step="0.01"
                                min="0"
                                value={line.unit_amount}
                                onChange={(e) =>
                                    setLine(i, { unit_amount: e.target.value })
                                }
                                placeholder="2000"
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={() => removeLine(i)}
                                disabled={lines.length === 1}
                                aria-label="Remove line item"
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        </div>
                    ))}
                </div>
                {lineError && (
                    <p className="text-xs text-destructive">{lineError}</p>
                )}
                <div className="mt-1 flex items-center justify-between">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={addLine}
                    >
                        <Plus className="mr-1.5 size-3.5" /> Add line
                    </Button>
                    <span className="text-sm">
                        <span className="text-muted-foreground">Total </span>
                        <span className="font-mono font-semibold tabular-nums">
                            {currency.symbol}
                            {total.toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2,
                            })}
                        </span>
                    </span>
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-1.5">
                    <Label className="text-xs">Valid for (days)</Label>
                    <Input
                        type="number"
                        min="1"
                        max="365"
                        value={validDays}
                        onChange={(e) =>
                            onValidDaysChange(Number(e.target.value))
                        }
                    />
                </div>
                <div className="grid gap-1.5">
                    <Label className="text-xs">
                        Delivery timeline (optional)
                    </Label>
                    <Input
                        value={delivery}
                        onChange={(e) => onDeliveryChange(e.target.value)}
                        placeholder="1–2 weeks from payment confirmation"
                    />
                </div>
            </div>
        </div>
    );
}
