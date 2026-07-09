import { useForm } from '@inertiajs/react';

import { Section } from '@/components/consultation/section';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useCurrency } from '@/hooks/use-currency';
import { price as priceRoute } from '@/routes/admin/consultations';

export function SetPriceCard({
    uuid,
    initial,
    stay = false,
    onDone,
    bare = false,
}: {
    uuid: string;
    initial?: { title: string; amount: string; expires_in_days: number };
    /** Stay on the consultation (used by the in-place manage dialog). */
    stay?: boolean;
    /** Called after a successful save (e.g. to close the dialog). */
    onDone?: () => void;
    /** Render just the form, without the surrounding Section card. */
    bare?: boolean;
}) {
    const currency = useCurrency();
    const editing = Boolean(initial && initial.amount !== '');
    const form = useForm({
        title: initial?.title ?? '',
        amount: initial?.amount ?? '',
        expires_in_days: initial?.expires_in_days ?? 14,
        stay: stay ? 1 : 0,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(priceRoute(uuid).url, {
            preserveScroll: true,
            onSuccess: () => onDone?.(),
        });
    };

    const formEl = (
        <form onSubmit={submit} className="grid gap-3">
            <div className="grid gap-1.5">
                <Label className="text-xs">Offer title (optional)</Label>
                <Input
                    value={form.data.title}
                    onChange={(e) => form.setData('title', e.target.value)}
                    placeholder="Custom Advisory — …"
                />
            </div>
            <div className="grid gap-1.5">
                <Label className="text-xs">Amount ({currency.code})</Label>
                <div className="flex">
                    <span className="inline-flex w-9 shrink-0 items-center justify-center rounded-l-md border border-r-0 border-input bg-muted text-sm font-medium text-muted-foreground">
                        {currency.symbol}
                    </span>
                    <Input
                        type="number"
                        step="0.01"
                        min="1"
                        value={form.data.amount}
                        onChange={(e) => form.setData('amount', e.target.value)}
                        placeholder="2500"
                        className="rounded-l-none"
                    />
                </div>
            </div>
            <div className="grid gap-1.5">
                <Label className="text-xs">Link valid for (days)</Label>
                <Input
                    type="number"
                    min="1"
                    max="365"
                    value={form.data.expires_in_days}
                    onChange={(e) =>
                        form.setData('expires_in_days', Number(e.target.value))
                    }
                />
            </div>
            <Button
                type="submit"
                disabled={form.processing}
                className="justify-self-start"
            >
                {form.processing && <Spinner className="mr-2" />}
                {editing ? 'Update price' : 'Set price'}
            </Button>
        </form>
    );

    if (bare) {
        return formEl;
    }

    return (
        <Section title={editing ? 'Edit price' : 'Set a price'} accent>
            <p className="mb-3 text-sm text-muted-foreground">
                {editing
                    ? 'Update the price for this tailored plan. The payment link stays the same — the client always pays the current amount.'
                    : 'This client requested a tailored plan. Set a price to generate a payment link you can send them.'}
            </p>
            {formEl}
        </Section>
    );
}
