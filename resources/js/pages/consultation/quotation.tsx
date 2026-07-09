import { zodResolver } from '@hookform/resolvers/zod';
import { Head, router } from '@inertiajs/react';
import { ArrowRight, CheckCircle2, Download, Lock } from 'lucide-react';
import { useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import Select from 'react-select';
import { z } from 'zod';

import { QuotationLines } from '@/components/consultation/quotation-lines';
import { CropMarks } from '@/components/guest/crop-marks';
import { GuestTopbar } from '@/components/guest/guest-topbar';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { Quotation, SelectOption } from '@/types';

const schema = z.object({
    name: z.string().min(1, 'Your name is required').max(255),
    email: z.string().min(1, 'Email is required').email('Enter a valid email'),
    // Company is intentionally optional — many clients quote as individuals.
    company: z.string().max(255).optional(),
    country: z.string().length(2).optional().or(z.literal('')),
});

type FormValues = z.infer<typeof schema>;

interface Props {
    quotation: Quotation;
    acceptUrl: string;
    pdfUrl: string;
    countries: SelectOption[];
    contact: {
        name: string | null;
        company: string | null;
        email: string | null;
        country: string | null;
    };
}

/** Small labelled key/value used in the document meta + party blocks. */
function Meta({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div>
            <p className="text-microcaps text-muted-foreground">{label}</p>
            <p className="mt-0.5 text-sm">{value ?? '—'}</p>
        </div>
    );
}

export default function QuotationPage({
    quotation,
    acceptUrl,
    pdfUrl,
    countries,
    contact,
}: Props) {
    const [processing, setProcessing] = useState(false);
    const {
        register,
        handleSubmit,
        control,
        setError,
        formState: { errors },
    } = useForm<FormValues>({
        resolver: zodResolver(schema),
        defaultValues: {
            name: contact.name ?? '',
            email: contact.email ?? '',
            company: contact.company ?? '',
            country: contact.country ?? '',
        },
    });

    const onSubmit = (values: FormValues) => {
        setProcessing(true);
        router.post(acceptUrl, values, {
            onError: (serverErrors) => {
                setProcessing(false);
                Object.entries(serverErrors).forEach(([key, message]) =>
                    setError(key as keyof FormValues, {
                        message: message as string,
                    }),
                );
            },
        });
    };

    const by = quotation.preparedBy;

    return (
        <>
            <Head title={`Quotation ${quotation.number}`} />
            <div className="min-h-screen bg-background text-foreground">
                <GuestTopbar step={1} backLabel="Return" />

                <div className="mx-auto w-full max-w-4xl px-6 py-12 sm:py-14">
                    {/* Document header */}
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p className="flex items-center gap-4 text-kicker text-primary">
                                Quotation
                                <span
                                    aria-hidden
                                    className="hairline-gold w-16"
                                />
                            </p>
                            <h1 className="mt-4 font-serif text-3xl leading-[1.06] font-semibold tracking-tight sm:text-4xl">
                                {quotation.title}
                            </h1>
                        </div>
                        <a
                            href={pdfUrl}
                            className="inline-flex items-center gap-2 rounded-lg border bg-card px-3.5 py-2 text-sm font-medium shadow-xs transition-colors hover:bg-muted/40"
                        >
                            <Download className="size-4" /> Download PDF
                        </a>
                    </div>

                    {quotation.expired && (
                        <div className="mt-6 rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
                            This quotation has expired. Please contact us for an
                            updated offer.
                        </div>
                    )}

                    <div className="mt-8 overflow-hidden surface-elevated">
                        {/* Meta row */}
                        <div className="grid grid-cols-2 gap-4 border-b bg-gradient-to-b from-muted/50 to-transparent px-6 py-5 sm:grid-cols-3">
                            <Meta
                                label="Quotation No."
                                value={quotation.number}
                            />
                            <Meta label="Date" value={quotation.issuedAt} />
                            <Meta
                                label="Validity"
                                value={
                                    quotation.validityDays !== null
                                        ? `${quotation.validityDays} days`
                                        : '—'
                                }
                            />
                        </div>

                        {/* Parties */}
                        <div className="grid gap-6 border-b px-6 py-6 sm:grid-cols-2">
                            <div>
                                <p className="text-microcaps text-brand-accent">
                                    Prepared by
                                </p>
                                <p className="mt-2 font-semibold">{by.name}</p>
                                {by.country && (
                                    <p className="text-sm text-muted-foreground">
                                        {by.country}
                                    </p>
                                )}
                                {by.email && (
                                    <p className="text-sm text-muted-foreground">
                                        {by.email}
                                    </p>
                                )}
                                {by.signatoryName && (
                                    <p className="mt-2 text-sm">
                                        <span className="font-medium">
                                            {by.signatoryName}
                                        </span>
                                        {by.signatoryTitle && (
                                            <span className="text-muted-foreground">
                                                {' '}
                                                · {by.signatoryTitle}
                                            </span>
                                        )}
                                    </p>
                                )}
                            </div>
                            <div>
                                <p className="text-microcaps text-brand-accent">
                                    Prepared for
                                </p>
                                <p className="mt-2 font-semibold">
                                    {quotation.preparedFor.name ?? '—'}
                                </p>
                                {quotation.preparedFor.company && (
                                    <p className="text-sm text-muted-foreground">
                                        {quotation.preparedFor.company}
                                    </p>
                                )}
                                {quotation.preparedFor.email && (
                                    <p className="text-sm text-muted-foreground">
                                        {quotation.preparedFor.email}
                                    </p>
                                )}
                                {quotation.preparedFor.country && (
                                    <p className="text-sm text-muted-foreground">
                                        {quotation.preparedFor.country}
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Scope of services */}
                        {quotation.scopeItems.length > 0 && (
                            <div className="border-b px-6 py-6">
                                <p className="text-microcaps text-muted-foreground">
                                    Scope of services
                                </p>
                                <ul className="mt-3 grid gap-x-6 sm:grid-cols-2">
                                    {quotation.scopeItems.map((item) => (
                                        <li
                                            key={item}
                                            className="flex items-start gap-3 py-1.5 text-sm"
                                        >
                                            <span
                                                aria-hidden
                                                className="mt-1 text-[10px] text-primary"
                                            >
                                                ✦
                                            </span>
                                            <span>{item}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        {/* Line items + totals */}
                        <div className="px-6 py-6">
                            <div className="sm:hidden">
                                <QuotationLines
                                    items={quotation.lineItems}
                                    variant="list"
                                />
                            </div>
                            <div className="hidden sm:block">
                                <QuotationLines
                                    items={quotation.lineItems}
                                    variant="table"
                                />
                            </div>

                            <div className="mt-4 ml-auto w-full max-w-xs space-y-1.5 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">
                                        Subtotal
                                    </span>
                                    <span className="font-mono tabular-nums">
                                        {quotation.subtotal}
                                    </span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">
                                        Taxes
                                    </span>
                                    <span className="font-mono tabular-nums">
                                        {quotation.taxLabel}
                                    </span>
                                </div>
                                <div className="flex justify-between border-t-[3px] border-double pt-2">
                                    <span className="font-serif font-semibold">
                                        Total Due
                                    </span>
                                    <span className="font-mono text-lg font-bold tabular-nums">
                                        {quotation.total}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Terms */}
                        <div className="space-y-5 border-t bg-muted/20 px-6 py-6 text-sm">
                            {quotation.deliveryTimeline && (
                                <div>
                                    <p className="font-serif font-semibold">
                                        Delivery timeline
                                    </p>
                                    <p className="mt-1 text-muted-foreground">
                                        {quotation.deliveryTimeline}
                                    </p>
                                </div>
                            )}
                            <div>
                                <p className="font-serif font-semibold">
                                    Payment terms
                                </p>
                                <ul className="mt-1 list-disc space-y-1 pl-5 text-muted-foreground">
                                    <li>
                                        100% payment upon acceptance of this
                                        quotation.
                                    </li>
                                    <li>
                                        Payment is processed through the secure{' '}
                                        {by.name} payment platform.
                                    </li>
                                    <li>
                                        Work begins once payment has been
                                        successfully received.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {/* Acceptance — confirm details & pay */}
                    <div className="relative mt-8 p-3">
                        <CropMarks />
                        <form
                            onSubmit={handleSubmit(onSubmit)}
                            className="overflow-hidden surface-card"
                        >
                            <div className="border-b px-6 py-4">
                                <p className="text-microcaps text-brand-accent">
                                    Acceptance
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Confirm your details, then accept &amp; pay
                                    to authorize {by.name} to begin.
                                </p>
                            </div>

                            <div className="grid gap-4 p-6 sm:grid-cols-2">
                                <div className="grid gap-1.5">
                                    <Label htmlFor="name">Client name</Label>
                                    <Input id="name" {...register('name')} />
                                    <InputError
                                        message={errors.name?.message}
                                    />
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="company">
                                        Company{' '}
                                        <span className="text-muted-foreground">
                                            (optional)
                                        </span>
                                    </Label>
                                    <Input
                                        id="company"
                                        {...register('company')}
                                    />
                                    <InputError
                                        message={errors.company?.message}
                                    />
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        {...register('email')}
                                    />
                                    <InputError
                                        message={errors.email?.message}
                                    />
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="country">
                                        Country{' '}
                                        <span className="text-muted-foreground">
                                            (optional)
                                        </span>
                                    </Label>
                                    <Controller
                                        control={control}
                                        name="country"
                                        render={({ field }) => (
                                            <Select
                                                inputId="country"
                                                options={countries}
                                                classNamePrefix="rs"
                                                placeholder="Select country"
                                                isClearable
                                                value={
                                                    countries.find(
                                                        (c) =>
                                                            c.value ===
                                                            field.value,
                                                    ) ?? null
                                                }
                                                onChange={(opt) =>
                                                    field.onChange(
                                                        opt?.value ?? '',
                                                    )
                                                }
                                            />
                                        )}
                                    />
                                    <InputError
                                        message={errors.country?.message}
                                    />
                                </div>
                            </div>

                            <div className="flex flex-col items-start gap-3 border-t bg-muted/30 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                    <Lock className="size-3.5 text-primary" />
                                    Secure payment · you won't be charged until
                                    the next step
                                </p>
                                <Button
                                    type="submit"
                                    disabled={processing || quotation.expired}
                                    size="lg"
                                >
                                    {processing ? (
                                        <Spinner className="mr-2" />
                                    ) : (
                                        <CheckCircle2 className="mr-2 size-4" />
                                    )}
                                    Accept &amp; pay {quotation.total}
                                    <ArrowRight className="ml-1 size-4" />
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
