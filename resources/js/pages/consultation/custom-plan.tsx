import { zodResolver } from '@hookform/resolvers/zod';
import { Head, router } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
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
import { store } from '@/routes/custom-plan';
import type { SelectOption } from '@/types';

const schema = z.object({
    name: z.string().min(1, 'Your name is required').max(255),
    email: z.string().min(1, 'Email is required').email('Enter a valid email'),
    company: z.string().max(255).optional(),
    country: z.string().length(2).optional().or(z.literal('')),
    budget: z.string().max(100).optional(),
    message: z
        .string()
        .min(1, 'Tell us what you need')
        .max(5000, 'Keep it under 5000 characters'),
});

type FormValues = z.infer<typeof schema>;

const STEPS = [
    {
        title: 'Tell us what you need',
        body: 'Describe your situation and goals — no account required.',
    },
    {
        title: 'We tailor a plan',
        body: 'Our team scopes the work and prices it for you.',
    },
    {
        title: 'Pay securely',
        body: 'You get a secure payment link by email — pay when ready.',
    },
];

export default function CustomPlanRequest({
    countries: countryOptions,
}: {
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
            company: '',
            country: '',
            budget: '',
            message: '',
        },
    });

    const onSubmit = (values: FormValues) => {
        setProcessing(true);
        router.post(store().url, values, {
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
            <Head title="Request a custom plan" />
            <div className="min-h-screen bg-background text-foreground">
                <GuestTopbar backLabel="Back to engagements" />

                <div className="mx-auto w-full max-w-6xl px-6 py-12 sm:py-16">
                    <div className="max-w-2xl">
                        <p className="flex items-center gap-4 text-kicker text-primary">
                            Tailored engagement
                            <span aria-hidden className="hairline-gold w-16" />
                        </p>
                        <h1 className="mt-4 font-serif text-3xl leading-[1.06] font-semibold tracking-tight sm:text-4xl">
                            Request a{' '}
                            <em className="text-gold-gradient">custom plan</em>
                        </h1>
                        <p className="mt-3 text-muted-foreground">
                            Don't see an engagement that fits? Tell us what you
                            need and we'll tailor one — then send you a secure
                            payment link.
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
                                        <Label htmlFor="company">Company</Label>
                                        <Input
                                            id="company"
                                            {...register('company')}
                                        />
                                        <InputError
                                            message={errors.company?.message}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="budget">
                                            Budget (optional)
                                        </Label>
                                        <Input
                                            id="budget"
                                            placeholder="e.g. $5,000"
                                            {...register('budget')}
                                        />
                                        <InputError
                                            message={errors.budget?.message}
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
                                        What do you need help with? *
                                    </Label>
                                    <Textarea
                                        id="message"
                                        rows={5}
                                        placeholder="Describe your situation, goals, and any deadlines…"
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
                                    Send request
                                    <ArrowRight className="size-4 transition-transform group-hover:translate-x-0.5" />
                                </Button>
                                <p className="text-center text-xs text-muted-foreground">
                                    We review every request personally and reply
                                    with a tailored quote within one business
                                    day.
                                </p>
                            </form>
                        </div>

                        <aside
                            className="relative h-fit animate-fade-up p-3 lg:sticky lg:top-10"
                            style={{ animationDelay: '80ms' }}
                        >
                            <CropMarks />
                            <div className="surface-card p-6">
                                <p className="text-microcaps text-brand-accent">
                                    How it works
                                </p>
                                <ol className="mt-5">
                                    {STEPS.map((step, i) => (
                                        <li
                                            key={step.title}
                                            className="group grid grid-cols-[56px_1fr] items-start gap-4 border-b border-border/60 py-5 first:pt-0 last:border-0 last:pb-0"
                                        >
                                            <span
                                                aria-hidden
                                                className="font-serif text-4xl leading-none font-semibold text-primary/25 italic select-none"
                                            >
                                                {i + 1}
                                            </span>
                                            <div className="min-w-0">
                                                <p className="font-serif text-base font-semibold">
                                                    {step.title}
                                                </p>
                                                <p className="mt-1 text-sm leading-relaxed text-muted-foreground">
                                                    {step.body}
                                                </p>
                                            </div>
                                        </li>
                                    ))}
                                </ol>
                                <p className="mt-5 border-t border-border pt-4 text-microcaps text-muted-foreground">
                                    Reviewed personally — one business day
                                </p>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </>
    );
}
