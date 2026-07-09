import { useForm } from '@inertiajs/react';

import { QuotationFields } from '@/components/consultation/quotation-fields';
import type { QuotationLineInput } from '@/components/consultation/quotation-fields';
import { Section } from '@/components/consultation/section';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { quotation as quotationRoute } from '@/routes/admin/consultations';

export interface QuotationInitial {
    title: string;
    scope_items: string[];
    line_items: QuotationLineInput[];
    expires_in_days: number;
    delivery_timeline: string;
    issued: boolean;
}

export interface TemplateOption {
    uuid: string;
    name: string;
    title: string;
    total: string;
    scopeItems: string[];
    lineItems: QuotationLineInput[];
    deliveryTimeline: string | null;
    validDays: number;
}

const EMPTY_LINE: QuotationLineInput = {
    description: '',
    quantity: 1,
    unit_amount: '',
};

/**
 * Admin builder for a formal quotation: a bulleted scope of services and an
 * itemised line-items table. The total is derived from the lines (server-side) —
 * never entered directly — so the document and the charge can't disagree.
 * Optionally start from a saved template.
 */
export function QuotationBuilderCard({
    uuid,
    initial,
    templates = [],
}: {
    uuid: string;
    initial: QuotationInitial;
    templates?: TemplateOption[];
}) {
    const form = useForm({
        title: initial.title,
        scope_items: initial.scope_items.length ? initial.scope_items : [''],
        line_items: initial.line_items.length
            ? initial.line_items
            : [{ ...EMPTY_LINE }],
        expires_in_days: initial.expires_in_days,
        delivery_timeline: initial.delivery_timeline,
    });

    const applyTemplate = (templateUuid: string) => {
        const t = templates.find((tpl) => tpl.uuid === templateUuid);

        if (!t) {
            return;
        }

        form.setData({
            ...form.data,
            title: t.title,
            scope_items: t.scopeItems.length ? t.scopeItems : [''],
            line_items: t.lineItems.length ? t.lineItems : [{ ...EMPTY_LINE }],
            delivery_timeline: t.deliveryTimeline ?? '',
            expires_in_days: t.validDays,
        });
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({
            ...data,
            scope_items: data.scope_items.filter((s) => s.trim() !== ''),
        }));
        form.post(quotationRoute(uuid).url, { preserveScroll: true });
    };

    return (
        <Section
            title={initial.issued ? 'Edit quotation' : 'Build a quotation'}
            accent
        >
            <p className="mb-4 text-sm text-muted-foreground">
                {initial.issued
                    ? 'Update the scope and pricing. The quotation number and link stay the same — the client always sees the current version.'
                    : 'Compose the scope of services and line items. A document number (BDA-…) is assigned automatically and the total is calculated from the lines.'}
            </p>

            <form onSubmit={submit} className="grid gap-5">
                {templates.length > 0 && (
                    <div className="grid gap-1.5">
                        <Label className="text-xs">Start from a template</Label>
                        <Select onValueChange={applyTemplate}>
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Pick a template to prefill…" />
                            </SelectTrigger>
                            <SelectContent>
                                {templates.map((t) => (
                                    <SelectItem key={t.uuid} value={t.uuid}>
                                        {t.name} · {t.total}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                )}

                <div className="grid gap-1.5">
                    <Label className="text-xs">Title</Label>
                    <Input
                        value={form.data.title}
                        onChange={(e) => form.setData('title', e.target.value)}
                        placeholder="Strategic Business Assessment"
                    />
                    {form.errors.title && (
                        <p className="text-xs text-destructive">
                            {form.errors.title}
                        </p>
                    )}
                </div>

                <QuotationFields
                    scope={form.data.scope_items}
                    onScopeChange={(v) => form.setData('scope_items', v)}
                    lines={form.data.line_items}
                    onLinesChange={(v) => form.setData('line_items', v)}
                    validDays={form.data.expires_in_days}
                    onValidDaysChange={(v) =>
                        form.setData('expires_in_days', v)
                    }
                    delivery={form.data.delivery_timeline}
                    onDeliveryChange={(v) =>
                        form.setData('delivery_timeline', v)
                    }
                    lineError={form.errors.line_items}
                />

                <Button
                    type="submit"
                    disabled={form.processing}
                    className="justify-self-start"
                >
                    {form.processing && <Spinner className="mr-2" />}
                    {initial.issued ? 'Update quotation' : 'Issue quotation'}
                </Button>
            </form>
        </Section>
    );
}
