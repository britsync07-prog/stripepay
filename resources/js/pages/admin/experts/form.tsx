import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    Clock,
    RefreshCw,
    Wallet,
} from 'lucide-react';

import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Panel } from '@/components/panel';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { experts as expertsIndex } from '@/routes/admin';
import { store, update } from '@/routes/admin/experts';
import { onboard, refresh } from '@/routes/admin/experts/payout';
import type { Expert, SelectOption } from '@/types';

export default function ExpertForm({
    expert,
    payoutMethods,
}: {
    expert: Expert | null;
    payoutMethods: SelectOption[];
}) {
    const editing = !!expert;

    const { data, setData, post, patch, processing, errors } = useForm({
        name: expert?.name ?? '',
        email: expert?.email ?? '',
        specialization: expert?.specialization ?? '',
        country: expert?.country ?? '',
        is_active: expert?.is_active ?? true,
        preferred_payout_method: expert?.preferred_payout_method ?? 'stripe',
        payout_notes: expert?.payout_notes ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (editing) {
            patch(update(expert!.id).url, { preserveScroll: true });
        } else {
            post(store().url);
        }
    };

    return (
        <>
            <Head title={editing ? 'Edit expert' : 'Add expert'} />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <Link
                    href={expertsIndex()}
                    className="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft className="size-4" /> All experts
                </Link>

                <PageHeader
                    eyebrow="Network"
                    title={editing ? 'Edit expert' : 'Add expert'}
                    description={
                        editing
                            ? 'Update this expert’s details and payout setup.'
                            : 'Add a new expert to the roster, then set up how they get paid.'
                    }
                />

                <div className="grid items-start gap-6 lg:grid-cols-3">
                    <Panel className="lg:col-span-2">
                        <form onSubmit={submit} className="space-y-5">
                            <div className="grid gap-5 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) =>
                                            setData('name', e.target.value)
                                        }
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) =>
                                            setData('email', e.target.value)
                                        }
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="country">
                                        Country (2-letter)
                                    </Label>
                                    <Input
                                        id="country"
                                        value={data.country}
                                        maxLength={2}
                                        onChange={(e) =>
                                            setData(
                                                'country',
                                                e.target.value.toUpperCase(),
                                            )
                                        }
                                    />
                                    <InputError message={errors.country} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="specialization">
                                        Specialization
                                    </Label>
                                    <Input
                                        id="specialization"
                                        value={data.specialization}
                                        onChange={(e) =>
                                            setData(
                                                'specialization',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={errors.specialization}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label>Preferred payout method</Label>
                                    <Select
                                        value={data.preferred_payout_method}
                                        onValueChange={(v) =>
                                            setData(
                                                'preferred_payout_method',
                                                v,
                                            )
                                        }
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {payoutMethods.map((m) => (
                                                <SelectItem
                                                    key={m.value}
                                                    value={m.value}
                                                >
                                                    {m.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={errors.preferred_payout_method}
                                    />
                                </div>

                                <div className="grid gap-2 sm:col-span-3">
                                    <Label htmlFor="payout_notes">
                                        Payout notes (optional)
                                    </Label>
                                    <textarea
                                        id="payout_notes"
                                        rows={2}
                                        value={data.payout_notes}
                                        onChange={(e) =>
                                            setData(
                                                'payout_notes',
                                                e.target.value,
                                            )
                                        }
                                        className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/35 focus-visible:outline-none"
                                    />
                                    <InputError message={errors.payout_notes} />
                                </div>
                            </div>

                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={data.is_active}
                                    onCheckedChange={(v) =>
                                        setData('is_active', v === true)
                                    }
                                />
                                Active
                            </label>
                            <InputError message={errors.is_active} />

                            <div className="flex justify-end gap-3 border-t pt-5">
                                <Button asChild variant="ghost">
                                    <Link href={expertsIndex()}>Cancel</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner className="mr-2" />}
                                    {editing ? 'Save changes' : 'Add expert'}
                                </Button>
                            </div>
                        </form>
                    </Panel>

                    <aside className="lg:sticky lg:top-6">
                        <div className="overflow-hidden surface-card">
                            <div className="flex items-center justify-between gap-3 border-b bg-gradient-to-br from-primary/[0.07] to-transparent px-5 py-4 sm:px-6">
                                <div className="flex items-center gap-3">
                                    <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary ring-1 ring-primary/15">
                                        <Wallet className="size-4.5" />
                                    </span>
                                    <div className="space-y-0.5">
                                        <p className="text-[0.7rem] font-medium tracking-wide text-primary/80 uppercase">
                                            Money out
                                        </p>
                                        <h2 className="font-serif text-base leading-none font-semibold">
                                            Stripe payouts
                                        </h2>
                                    </div>
                                </div>
                                {editing && expert && (
                                    <Badge
                                        variant="outline"
                                        className={`shrink-0 gap-1.5 rounded-full border-transparent px-2.5 py-1 ${
                                            expert.stripe_payouts_enabled
                                                ? 'bg-success/10 text-success ring-1 ring-success/20'
                                                : expert.stripe_account_id
                                                  ? 'bg-warning/15 text-warning-foreground ring-1 ring-warning/25 dark:text-warning'
                                                  : 'bg-muted text-muted-foreground ring-1 ring-border'
                                        }`}
                                    >
                                        <span className="size-1.5 rounded-full bg-current" />
                                        {expert.stripe_payouts_enabled
                                            ? 'Active'
                                            : expert.stripe_account_id
                                              ? 'Pending'
                                              : 'Not started'}
                                    </Badge>
                                )}
                            </div>

                            <div className="space-y-4 p-5 sm:p-6">
                                {!editing || !expert ? (
                                    <p className="text-sm leading-relaxed text-muted-foreground">
                                        Save this expert first, then set up
                                        payout onboarding here (or right from
                                        the payouts page when you go to pay
                                        them).
                                    </p>
                                ) : (
                                    <>
                                        {expert.stripe_payouts_enabled ? (
                                            <div className="flex items-start gap-3 rounded-xl border border-success/20 bg-success/[0.06] p-3.5">
                                                <CheckCircle2 className="mt-0.5 size-5 shrink-0 text-success" />
                                                <p className="text-sm leading-relaxed text-foreground">
                                                    <span className="font-medium">
                                                        Onboarding complete.
                                                    </span>{' '}
                                                    This expert can be paid via
                                                    Stripe.
                                                </p>
                                            </div>
                                        ) : expert.stripe_account_id ? (
                                            <div className="flex items-start gap-3 rounded-xl border border-warning/25 bg-warning/[0.08] p-3.5">
                                                <Clock className="mt-0.5 size-5 shrink-0 text-warning-foreground dark:text-warning" />
                                                <p className="text-sm leading-relaxed text-foreground">
                                                    <span className="font-medium">
                                                        Onboarding started.
                                                    </span>{' '}
                                                    Identity &amp; bank/card
                                                    details still need to be
                                                    finished in Stripe.
                                                </p>
                                            </div>
                                        ) : (
                                            <div className="flex items-start gap-3 rounded-xl border bg-muted/40 p-3.5">
                                                <Wallet className="mt-0.5 size-5 shrink-0 text-muted-foreground" />
                                                <p className="text-sm leading-relaxed text-muted-foreground">
                                                    Start onboarding so this
                                                    expert can enter their
                                                    bank/card &amp; identity
                                                    details — you can do it for
                                                    them or send them the link.
                                                </p>
                                            </div>
                                        )}

                                        <div className="flex flex-col gap-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                className="w-full justify-center"
                                                onClick={() =>
                                                    router.post(
                                                        onboard(expert.id).url,
                                                    )
                                                }
                                            >
                                                <Wallet className="size-4" />
                                                {expert.stripe_account_id
                                                    ? 'Re-open Stripe onboarding'
                                                    : 'Set up Stripe payouts'}
                                            </Button>

                                            {expert.stripe_account_id &&
                                                !expert.stripe_payouts_enabled && (
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        className="w-full justify-center"
                                                        onClick={() =>
                                                            router.get(
                                                                refresh(
                                                                    expert.id,
                                                                ).url,
                                                            )
                                                        }
                                                    >
                                                        <RefreshCw className="size-4" />
                                                        Refresh status
                                                    </Button>
                                                )}
                                        </div>
                                    </>
                                )}
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </>
    );
}
