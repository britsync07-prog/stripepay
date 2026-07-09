import { zodResolver } from '@hookform/resolvers/zod';
import { Head, router } from '@inertiajs/react';
import { ArrowRight, Lock } from 'lucide-react';
import { useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import Select from 'react-select';
import { z } from 'zod';

import { CropMarks } from '@/components/guest/crop-marks';
import { GuestTopbar } from '@/components/guest/guest-topbar';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { store } from '@/routes/consultation';
import type { Plan, SelectOption } from '@/types';

const schema = z.object({
    name: z.string().min(1, 'Your name is required').max(255),
    email: z.string().min(1, 'Email is required').email('Enter a valid email'),
    phone: z.string().max(50).optional(),
    company: z.string().max(255).optional(),
    country: z.string().length(2).optional().or(z.literal('')),
    message: z.string().max(5000).optional(),
});

type FormValues = z.infer<typeof schema>;

export default function ConsultationRequest({
    plan,
    countries: countryOptions,
}: {
    plan: Plan;
    countries: SelectOption[];
}) {
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
            name: '',
            email: '',
            phone: '',
            company: '',
            country: '',
            message: '',
        },
    });

    const onSubmit = (values: FormValues) => {
        setProcessing(true);
        router.post(store(plan.slug).url, values, {
            onError: (serverErrors) => {
                Object.entries(serverErrors).forEach(([field, message]) =>
                    setError(field as keyof FormValues, {
                        message: message as string,
                    }),
                );
                setProcessing(false);
            },
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <>
            <Head title={`Request — ${plan.name}`} />
            <div className="min-h-screen bg-background text-foreground">
                <GuestTopbar step={1} backLabel="Back to engagements" />

                <div className="mx-auto w-full max-w-6xl px-6 py-12 sm:py-16">
                    <div className="max-w-2xl">
                        <p className="flex items-center gap-4 text-kicker text-primary">
                            Intake — 01
                            <span aria-hidden className="hairline-gold w-16" />
                        </p>
                        <h1 className="mt-4 font-serif text-3xl leading-[1.06] font-semibold tracking-tight sm:text-4xl">
                            Request a{' '}
                            <em className="text-gold-gradient">consultation</em>
                        </h1>
                        <p className="mt-3 text-muted-foreground">
                            Tell us about yourself. No account needed — you'll
                            pay securely on the next step.
                        </p>
                    </div>

                    <div className="mt-10 grid items-start gap-8 lg:grid-cols-[1fr_380px]">
                        <div className="animate-fade-up surface-card p-6 sm:p-9">
                            <form
                                onSubmit={handleSubmit(onSubmit)}
                                className="space-y-6"
                            >
                                <div className="grid gap-6 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">
                                            Full name *
                                        </Label>
                                        <Input
                                            id="name"
                                            autoFocus
                                            {...register('name')}
                                        />
                                        <InputError
                                            message={errors.name?.message}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Email *</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            {...register('email')}
                                        />
                                        <InputError
                                            message={errors.email?.message}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="phone">Phone</Label>
                                        <Input
                                            id="phone"
                                            {...register('phone')}
                                        />
                                        <InputError
                                            message={errors.phone?.message}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="company">Company</Label>
                                        <Input
                                            id="company"
                                            {...register('company')}
                                        />
                                        <InputError
                                            message={errors.company?.message}
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="country">Country</Label>
                                    <Controller
                                        control={control}
                                        name="country"
                                        render={({ field }) => (
                                            <Select
                                                inputId="country"
                                                options={countryOptions}
                                                classNamePrefix="rs"
                                                placeholder="Select your country"
                                                isClearable
                                                value={
                                                    countryOptions.find(
                                                        (o) =>
                                                            o.value ===
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

                                <div className="grid gap-2">
                                    <Label htmlFor="message">
                                        What do you need help with?
                                    </Label>
                                    <Textarea
                                        id="message"
                                        rows={4}
                                        {...register('message')}
                                    />
                                    <InputError
                                        message={errors.message?.message}
                                    />
                                </div>

                                <Button
                                    type="submit"
                                    size="lg"
                                    className="group w-full"
                                    disabled={processing}
                                >
                                    {processing && <Spinner className="mr-2" />}
                                    Continue to payment — {plan.formatted_price}
                                    <ArrowRight className="size-4 transition-transform group-hover:translate-x-0.5" />
                                </Button>
                                <p className="flex items-center justify-center gap-1.5 text-center text-xs text-muted-foreground">
                                    <Lock className="size-3 text-primary" />
                                    You won't be charged yet — payment is on the
                                    next step.
                                </p>
                            </form>
                        </div>

                        <aside
                            className="relative h-fit animate-fade-up p-3 lg:sticky lg:top-10"
                            style={{ animationDelay: '80ms' }}
                        >
                            <CropMarks />
                            <div className="surface-card p-6">
                                <div className="flex items-center justify-between gap-3">
                                    <p className="text-microcaps text-brand-accent">
                                        Engagement summary
                                    </p>
                                    {plan.is_featured && (
                                        <span className="inline-flex items-center gap-1 rounded-full border border-primary/40 bg-primary/10 px-2 py-0.5 text-microcaps text-brand-accent">
                                            <span aria-hidden>✦</span> Most
                                            engaged
                                        </span>
                                    )}
                                </div>
                                <h2 className="mt-4 font-serif text-xl font-semibold">
                                    {plan.name}
                                </h2>
                                <div className="mt-1.5 flex items-baseline gap-1.5">
                                    <span className="font-mono text-3xl font-bold tracking-tight tabular-nums">
                                        {plan.formatted_price}
                                    </span>
                                    <span className="text-sm text-muted-foreground">
                                        {plan.interval_label}
                                    </span>
                                </div>
                                {plan.description && (
                                    <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                                        {plan.description}
                                    </p>
                                )}
                                <ul className="mt-5">
                                    {plan.features.map((feature) => (
                                        <li
                                            key={feature}
                                            className="flex items-start gap-3 border-b border-border/60 py-2.5 text-sm last:border-0"
                                        >
                                            <span
                                                aria-hidden
                                                className="mt-1 text-[10px] text-primary"
                                            >
                                                ✦
                                            </span>
                                            <span>{feature}</span>
                                        </li>
                                    ))}
                                </ul>
                                <p className="mt-5 border-t border-border pt-4 text-microcaps text-muted-foreground">
                                    Next — 02 · Secure payment
                                </p>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </>
    );
}
