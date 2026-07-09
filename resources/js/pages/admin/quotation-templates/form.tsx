import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { QuotationFields } from '@/components/consultation/quotation-fields';
import type { QuotationLineInput } from '@/components/consultation/quotation-fields';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Panel } from '@/components/panel';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { quotationTemplates } from '@/routes/admin';
import { store, update } from '@/routes/admin/quotation-templates';

interface Template {
    uuid: string;
    name: string;
    title: string;
    scopeItems: string[];
    lineItems: QuotationLineInput[];
    deliveryTimeline: string | null;
    validDays: number;
    isActive: boolean;
}

const EMPTY_LINE: QuotationLineInput = {
    description: '',
    quantity: 1,
    unit_amount: '',
};

export default function QuotationTemplateForm({
    template,
}: {
    template: Template | null;
}) {
    const editing = Boolean(template);
    const form = useForm({
        name: template?.name ?? '',
        title: template?.title ?? '',
        scope_items: template?.scopeItems.length ? template.scopeItems : [''],
        line_items: template?.lineItems.length
            ? template.lineItems
            : [{ ...EMPTY_LINE }],
        delivery_timeline: template?.deliveryTimeline ?? '',
        valid_days: template?.validDays ?? 30,
        is_active: template?.isActive ?? true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({
            ...data,
            scope_items: data.scope_items.filter((s) => s.trim() !== ''),
        }));

        if (editing && template) {
            form.patch(update(template.uuid).url);
        } else {
            form.post(store().url);
        }
    };

    return (
        <>
            <Head title={editing ? 'Edit template' : 'New template'} />
            <div className="flex flex-1 flex-col gap-5 p-4 sm:p-6">
                <Link
                    href={quotationTemplates().url}
                    className="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft className="size-4" /> Back to templates
                </Link>

                <PageHeader
                    eyebrow="Quotation templates"
                    title={editing ? 'Edit template' : 'New template'}
                    description="A reusable scope + line items you can drop onto a quotation in one click."
                />

                <form onSubmit={submit} className="grid gap-6">
                    <Panel eyebrow="Preset" title="Details">
                        <div className="grid gap-5">
                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Template name</Label>
                                    <Input
                                        id="name"
                                        value={form.data.name}
                                        onChange={(e) =>
                                            form.setData('name', e.target.value)
                                        }
                                        placeholder="Strategic Business Assessment"
                                    />
                                    <InputError message={form.errors.name} />
                                    <p className="text-xs text-muted-foreground">
                                        Internal label — how you'll find it in
                                        the picker.
                                    </p>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="title">
                                        Quotation title
                                    </Label>
                                    <Input
                                        id="title"
                                        value={form.data.title}
                                        onChange={(e) =>
                                            form.setData(
                                                'title',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Strategic Business Assessment"
                                    />
                                    <InputError message={form.errors.title} />
                                    <p className="text-xs text-muted-foreground">
                                        Shown to the client on the quotation.
                                    </p>
                                </div>
                            </div>

                            <label className="flex items-center gap-3">
                                <Checkbox
                                    checked={form.data.is_active}
                                    onCheckedChange={(v) =>
                                        form.setData('is_active', v === true)
                                    }
                                />
                                <span className="text-sm">
                                    Active — available in the template picker
                                </span>
                            </label>
                        </div>
                    </Panel>

                    <Panel eyebrow="Offer" title="Scope & pricing">
                        <QuotationFields
                            scope={form.data.scope_items}
                            onScopeChange={(v) =>
                                form.setData('scope_items', v)
                            }
                            lines={form.data.line_items}
                            onLinesChange={(v) => form.setData('line_items', v)}
                            validDays={form.data.valid_days}
                            onValidDaysChange={(v) =>
                                form.setData('valid_days', v)
                            }
                            delivery={form.data.delivery_timeline}
                            onDeliveryChange={(v) =>
                                form.setData('delivery_timeline', v)
                            }
                            lineError={form.errors.line_items}
                        />
                    </Panel>

                    <div className="flex justify-end gap-2">
                        <Button variant="ghost" asChild>
                            <Link href={quotationTemplates().url}>Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner className="mr-2" />}
                            {editing ? 'Save template' : 'Create template'}
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}
