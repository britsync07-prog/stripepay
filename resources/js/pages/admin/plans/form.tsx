import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Minus, Plus } from 'lucide-react';
import { useState } from 'react';

import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Panel } from '@/components/panel';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useCurrency } from '@/hooks/use-currency';
import { plans as plansIndex } from '@/routes/admin';
import { store, update } from '@/routes/admin/plans';
import type { Plan } from '@/types';

const slugify = (value: string) =>
    value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

function Section({
    title,
    description,
    children,
}: {
    title: string;
    description?: string;
    children: React.ReactNode;
}) {
    return (
        <Panel title={title} description={description}>
            <div className="grid gap-4">{children}</div>
        </Panel>
    );
}

export default function PlanForm({ plan }: { plan: Plan | null }) {
    const editing = !!plan;
    const currency = useCurrency();
    const [slugTouched, setSlugTouched] = useState(editing);

    const { data, setData, transform, post, patch, processing, errors } =
        useForm({
            name: plan?.name ?? '',
            slug: plan?.slug ?? '',
            description: plan?.description ?? '',
            price: plan ? String(plan.price / 100) : '',
            // Billing interval is display-only (one-time charge); fixed to
            // per-engagement and not surfaced in the form.
            billing_interval: plan?.billing_interval ?? 'per_engagement',
            features: plan?.features?.length ? plan.features : [''],
            outcome: plan?.outcome ?? '',
            cta_label: plan?.cta_label ?? 'Pay now',
            is_featured: plan?.is_featured ?? false,
            is_active: plan?.is_active ?? true,
        });

    transform((d) => ({
        ...d,
        features: d.features.map((f) => f.trim()).filter(Boolean),
    }));

    const addFeature = () => setData('features', [...data.features, '']);
    const removeFeature = (index: number) =>
        setData(
            'features',
            data.features.filter((_, i) => i !== index),
        );
    const updateFeature = (index: number, value: string) =>
        setData(
            'features',
            data.features.map((f, i) => (i === index ? value : f)),
        );

    const onName = (value: string) => {
        setData((prev) => ({
            ...prev,
            name: value,
            slug: slugTouched ? prev.slug : slugify(value),
        }));
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (editing) {
            patch(update(plan!.id!).url, { preserveScroll: true });
        } else {
            post(store().url);
        }
    };

    return (
        <>
            <Head title={editing ? 'Edit plan' : 'Add plan'} />
            <div className="flex w-full flex-1 flex-col gap-6 p-4 sm:p-6">
                <Link
                    href={plansIndex()}
                    className="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft className="size-4" /> All plans
                </Link>

                <PageHeader
                    eyebrow="Catalog"
                    title={editing ? 'Edit plan' : 'Add plan'}
                    description={
                        editing
                            ? 'Update this plan’s pricing, features, and visibility.'
                            : 'Create a new plan to offer on the public site.'
                    }
                />

                <form onSubmit={submit} className="flex flex-col gap-6">
                    <div className="grid items-start gap-6 lg:grid-cols-2">
                        <div className="flex flex-col gap-6">
                            <Section
                                title="Details"
                                description="Basic information shown on the public site."
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => onName(e.target.value)}
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="slug">URL slug</Label>
                                    <Input
                                        id="slug"
                                        value={data.slug}
                                        onChange={(e) => {
                                            setSlugTouched(true);
                                            setData(
                                                'slug',
                                                slugify(e.target.value),
                                            );
                                        }}
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Used in the public link: /plans/
                                        <span className="font-mono">
                                            {data.slug || 'your-slug'}
                                        </span>
                                        /buy
                                    </p>
                                    <InputError message={errors.slug} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="description">
                                        Description
                                    </Label>
                                    <Textarea
                                        id="description"
                                        rows={2}
                                        value={data.description}
                                        onChange={(e) =>
                                            setData(
                                                'description',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={errors.description} />
                                </div>
                            </Section>

                            <Section
                                title="Pricing"
                                description="What the client pays (one-time)."
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="price">Price</Label>
                                    <div className="flex">
                                        <Input
                                            id="price"
                                            type="number"
                                            min={0}
                                            step="0.01"
                                            value={data.price}
                                            onChange={(e) =>
                                                setData('price', e.target.value)
                                            }
                                            className="rounded-r-none focus-visible:z-10"
                                        />
                                        <span className="inline-flex w-20 shrink-0 items-center justify-center rounded-r-md border border-l-0 border-input bg-muted text-sm font-medium tracking-wide text-muted-foreground">
                                            {currency.code}
                                        </span>
                                    </div>
                                    <InputError message={errors.price} />
                                </div>
                            </Section>
                        </div>

                        <div className="flex flex-col gap-6">
                            <Section
                                title="Marketing"
                                description="Copy that helps the plan convert."
                            >
                                <div className="grid gap-2">
                                    <Label>Features</Label>
                                    <div className="grid gap-2">
                                        {data.features.map((feature, index) => (
                                            <div
                                                key={index}
                                                className="flex items-center gap-2"
                                            >
                                                <Input
                                                    value={feature}
                                                    onChange={(e) =>
                                                        updateFeature(
                                                            index,
                                                            e.target.value,
                                                        )
                                                    }
                                                    placeholder={`Feature ${index + 1}`}
                                                />
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-9 shrink-0 text-muted-foreground hover:text-destructive"
                                                    aria-label="Remove feature"
                                                    onClick={() =>
                                                        removeFeature(index)
                                                    }
                                                    disabled={
                                                        data.features.length ===
                                                        1
                                                    }
                                                >
                                                    <Minus className="size-4" />
                                                </Button>
                                            </div>
                                        ))}
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="w-fit"
                                        onClick={addFeature}
                                    >
                                        <Plus className="size-4" /> Add feature
                                    </Button>
                                    <InputError message={errors.features} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="outcome">
                                        Outcome (optional)
                                    </Label>
                                    <Input
                                        id="outcome"
                                        value={data.outcome}
                                        onChange={(e) =>
                                            setData('outcome', e.target.value)
                                        }
                                    />
                                    <InputError message={errors.outcome} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="cta_label">
                                        Button label
                                    </Label>
                                    <Input
                                        id="cta_label"
                                        value={data.cta_label}
                                        onChange={(e) =>
                                            setData('cta_label', e.target.value)
                                        }
                                    />
                                    <InputError message={errors.cta_label} />
                                </div>
                            </Section>

                            <Section
                                title="Visibility"
                                description="Control where this plan appears. New plans are added to the end — drag to reorder on the Plans list."
                            >
                                <div className="flex flex-wrap gap-6">
                                    <label className="flex items-center gap-2 text-sm">
                                        <Checkbox
                                            checked={data.is_featured}
                                            onCheckedChange={(v) =>
                                                setData(
                                                    'is_featured',
                                                    v === true,
                                                )
                                            }
                                        />
                                        Featured (“Most engaged”)
                                    </label>
                                    <label className="flex items-center gap-2 text-sm">
                                        <Checkbox
                                            checked={data.is_active}
                                            onCheckedChange={(v) =>
                                                setData('is_active', v === true)
                                            }
                                        />
                                        Active (visible on site)
                                    </label>
                                </div>
                            </Section>
                        </div>
                    </div>

                    <div className="flex justify-end gap-3 border-t pt-6">
                        <Button asChild variant="ghost">
                            <Link href={plansIndex()}>Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner className="mr-2" />}
                            {editing ? 'Save changes' : 'Create plan'}
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}
