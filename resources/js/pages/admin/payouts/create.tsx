import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    Clock,
    FileText,
    RefreshCw,
    UploadCloud,
    Wallet,
} from 'lucide-react';
import { useCallback, useState } from 'react';
import { useDropzone } from 'react-dropzone';

import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Panel } from '@/components/panel';
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
import { useCurrency } from '@/hooks/use-currency';
import { payouts } from '@/routes/admin';
import { onboard, refresh } from '@/routes/admin/experts/payout';
import { store } from '@/routes/admin/payouts';
import type { Expert, SelectOption } from '@/types';

type PayoutExpert = Pick<
    Expert,
    'id' | 'name' | 'stripe_account_id' | 'stripe_payouts_enabled'
>;

export default function CreatePayout({
    experts,
    payoutMethods,
    defaultMethod,
}: {
    experts: PayoutExpert[];
    payoutMethods: SelectOption[];
    defaultMethod: string;
}) {
    const currency = useCurrency();
    const [idempotencyKey] = useState(() => crypto.randomUUID());

    const { data, setData, post, processing, errors } = useForm<{
        expert_id: string;
        amount: string;
        method: string;
        note: string;
        proof: File | null;
        idempotency_key: string;
    }>({
        expert_id: '',
        amount: '',
        method: defaultMethod,
        note: '',
        proof: null,
        idempotency_key: idempotencyKey,
    });

    const selectedExpert = experts.find((e) => String(e.id) === data.expert_id);
    const expertReady = selectedExpert?.stripe_payouts_enabled === true;

    const { uploads } = usePage().props;

    const onDrop = useCallback(
        (files: File[]) => setData('proof', files[0] ?? null),
        [setData],
    );
    const { getRootProps, getInputProps, isDragActive } = useDropzone({
        onDrop,
        maxFiles: 1,
        accept: uploads.accept.proof,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store().url, { forceFormData: true });
    };

    return (
        <>
            <Head title="Send a payout" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <Link
                    href={payouts()}
                    className="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft className="size-4" /> All payouts
                </Link>

                <PageHeader
                    eyebrow="Expert payouts"
                    title="Send a payout"
                    description="Pay an expert through Stripe. They must have completed payout onboarding first; the transfer settles immediately."
                />

                <form
                    onSubmit={submit}
                    className="grid items-start gap-6 lg:grid-cols-3"
                >
                    <Panel className="lg:col-span-2" bodyClassName="space-y-5">
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div className="grid gap-2 sm:col-span-2">
                                <Label>Expert</Label>
                                <Select
                                    value={data.expert_id}
                                    onValueChange={(v) =>
                                        setData('expert_id', v)
                                    }
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Select expert" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {experts.map((ex) => (
                                            <SelectItem
                                                key={ex.id}
                                                value={String(ex.id)}
                                            >
                                                {ex.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.expert_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="amount">
                                    Amount ({currency.code})
                                </Label>
                                <div className="flex">
                                    <span className="inline-flex w-10 shrink-0 items-center justify-center rounded-l-md border border-r-0 border-input bg-muted text-sm font-medium text-muted-foreground">
                                        {currency.symbol}
                                    </span>
                                    <Input
                                        id="amount"
                                        type="number"
                                        step="0.01"
                                        min="0.5"
                                        value={data.amount}
                                        onChange={(e) =>
                                            setData('amount', e.target.value)
                                        }
                                        placeholder="4000"
                                        className="rounded-l-none"
                                    />
                                </div>
                                <InputError message={errors.amount} />
                            </div>

                            <div className="grid gap-2">
                                <Label>Method</Label>
                                <Select
                                    value={data.method}
                                    onValueChange={(v) => setData('method', v)}
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
                                <InputError message={errors.method} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="note">Note (optional)</Label>
                            <textarea
                                id="note"
                                rows={2}
                                value={data.note}
                                onChange={(e) =>
                                    setData('note', e.target.value)
                                }
                                className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/35 focus-visible:outline-none"
                            />
                            <InputError message={errors.note} />
                        </div>

                        <div className="grid gap-2">
                            <Label>Proof (receipt / invoice — optional)</Label>
                            <div
                                {...getRootProps()}
                                className={`flex cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border border-dashed p-5 text-center text-sm transition-colors ${
                                    isDragActive
                                        ? 'border-primary bg-primary/5'
                                        : 'hover:bg-muted/50'
                                }`}
                            >
                                <input {...getInputProps()} />
                                {data.proof ? (
                                    <span className="flex items-center gap-1.5 text-foreground">
                                        <FileText className="size-4 text-primary" />{' '}
                                        {data.proof.name}
                                    </span>
                                ) : (
                                    <>
                                        <UploadCloud className="size-5 text-muted-foreground" />
                                        <span className="text-muted-foreground">
                                            Drop a PDF/image or click to upload
                                        </span>
                                    </>
                                )}
                            </div>
                            <InputError message={errors.proof} />
                        </div>

                        <div className="flex justify-end gap-3 border-t pt-5">
                            <Button asChild variant="ghost">
                                <Link href={payouts()}>Cancel</Link>
                            </Button>
                            <Button
                                type="submit"
                                disabled={
                                    processing ||
                                    (!!selectedExpert && !expertReady)
                                }
                            >
                                {processing && <Spinner className="mr-2" />}
                                Send payout
                            </Button>
                        </div>
                    </Panel>

                    <aside className="lg:sticky lg:top-6">
                        <Panel bodyClassName="space-y-3">
                            <div className="flex items-center gap-2">
                                <Wallet className="size-4 text-muted-foreground" />
                                <h2 className="text-sm font-semibold">
                                    Payout readiness
                                </h2>
                            </div>

                            {!selectedExpert ? (
                                <p className="text-sm text-muted-foreground">
                                    Select an expert to check their Stripe
                                    onboarding status.
                                </p>
                            ) : expertReady ? (
                                <p className="flex items-start gap-1.5 text-sm text-success">
                                    <CheckCircle2 className="mt-0.5 size-4 shrink-0" />
                                    <span>
                                        {selectedExpert.name} is onboarded —
                                        ready for Stripe payouts.
                                    </span>
                                </p>
                            ) : (
                                <div className="space-y-3">
                                    <p className="flex items-start gap-1.5 text-sm text-warning-foreground dark:text-warning">
                                        <Clock className="mt-0.5 size-4 shrink-0" />
                                        <span>
                                            {selectedExpert.name} hasn’t
                                            finished Stripe onboarding. Start it
                                            here — you can complete it for them
                                            or send the link.
                                        </span>
                                    </p>
                                    <div className="flex flex-col gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() =>
                                                router.post(
                                                    onboard(selectedExpert.id)
                                                        .url,
                                                )
                                            }
                                        >
                                            <Wallet className="size-4" />
                                            {selectedExpert.stripe_account_id
                                                ? 'Re-open onboarding'
                                                : 'Start Stripe onboarding'}
                                        </Button>
                                        {selectedExpert.stripe_account_id && (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                onClick={() =>
                                                    router.get(
                                                        refresh(
                                                            selectedExpert.id,
                                                        ).url,
                                                    )
                                                }
                                            >
                                                <RefreshCw className="size-4" />
                                                Refresh status
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            )}
                        </Panel>
                    </aside>
                </form>
            </div>
        </>
    );
}
