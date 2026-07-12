import { Head } from '@inertiajs/react';
import { CreditCard, ShieldCheck } from 'lucide-react';
import { motion, useReducedMotion } from 'motion/react';
import { useState } from 'react';

import { PayPalCheckout, StripeEmbeddedCheckout } from '@/components/checkout';
import { QuotationLines } from '@/components/consultation/quotation-lines';
import { CropMarks } from '@/components/guest/crop-marks';
import { GuestTopbar } from '@/components/guest/guest-topbar';
import { CardBrands, PaypalMark } from '@/components/payment-brands';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { TRUST } from '@/constants/checkout';
import { EASE_SPRING } from '@/constants/motion';
import { useGateways } from '@/hooks/use-gateways';
import { cn } from '@/lib/utils';
import type { Gateway, Plan, Quotation } from '@/types';

interface Props {
    gateways: Gateway[];
    plan: Plan | null;
    quotation: Quotation | null;
    offer: { title: string; description: string | null; amount: string };
    request: { name: string; email: string };
}

/** Brand logos shown inside each gateway option. */
function GatewayBrand({ gateway }: { gateway: Gateway }) {
    const { keys } = useGateways();

    if (gateway.key === keys.paypal) {
        return <PaypalMark />;
    }

    if (gateway.key === keys.stripe) {
        return <CardBrands />;
    }

    return <span className="text-sm font-semibold">{gateway.label}</span>;
}

export default function Checkout({ gateways, plan, quotation, offer }: Props) {
    const reduce = useReducedMotion();
    const { modes } = useGateways();

    // Plan-based checkouts keep their richer formatted price; custom offers use
    // the request amount.
    const priceLabel = plan ? plan.formatted_price : offer.amount;

    // Nothing is loaded until the buyer explicitly starts a payment method.
    const [activeKey, setActiveKey] = useState<string>('');
    const active = gateways.find((g) => g.key === activeKey) ?? null;

    const fade = (delay = 0) => ({
        initial: { opacity: 0, y: reduce ? 0 : 16 },
        animate: { opacity: 1, y: 0 },
        transition: { duration: 0.55, ease: EASE_SPRING, delay },
    });

    return (
        <>
            <Head title="Secure checkout" />
            <div className="min-h-screen bg-background text-foreground">
                <GuestTopbar step={2} backLabel="Cancel and return" />

                <div className="mx-auto w-full max-w-6xl px-6 py-12 sm:py-14">
                    <motion.div className="max-w-2xl" {...fade()}>
                        <p className="flex items-center gap-4 text-kicker text-primary">
                            Payment — 02
                            <span aria-hidden className="hairline-gold w-16" />
                        </p>
                        <h1 className="mt-4 font-serif text-3xl leading-[1.06] font-semibold tracking-tight sm:text-4xl">
                            Complete your{' '}
                            <em className="text-gold-gradient">payment</em>
                        </h1>
                        <p className="mt-3 text-muted-foreground">
                            Review your engagement and pay securely below — no
                            account required.
                        </p>
                        <div className="mt-6 flex flex-wrap items-center gap-2.5">
                            {TRUST.map((t) => (
                                <Badge
                                    key={t.label}
                                    variant="outline"
                                    className="gap-1.5 rounded-full bg-card px-3 py-1.5 font-medium text-muted-foreground shadow-card [&>svg]:size-3.5"
                                >
                                    <t.icon className="text-primary" />
                                    {t.label}
                                </Badge>
                            ))}
                        </div>
                    </motion.div>

                    <div className="mt-10 grid items-start gap-8 lg:grid-cols-[1fr_380px]">
                        {/* Payment panel — single labelled frame so the Stripe form
                            reads as a deliberate section, not a floating widget. */}
                        <motion.section
                            className="order-2 overflow-hidden surface-card lg:order-1"
                            {...fade(0.04)}
                        >
                            <div className="flex items-center justify-between gap-3 border-b bg-gradient-to-b from-muted/50 to-transparent px-5 py-4 sm:px-6">
                                <div className="flex items-center gap-2.5">
                                    <span className="flex size-9 items-center justify-center rounded-xl bg-primary/10 text-primary ring-1 ring-primary/20 ring-inset">
                                        <CreditCard className="size-4" />
                                    </span>
                                    <div>
                                        <h2 className="text-sm font-semibold">
                                            Payment details
                                        </h2>
                                        <p className="text-xs text-muted-foreground">
                                            Card, wallet &amp; more
                                        </p>
                                    </div>
                                </div>
                                <span className="font-mono text-sm font-semibold tabular-nums">
                                    {priceLabel}
                                </span>
                            </div>

                            <div className="p-4 sm:p-6">
                                {gateways.length > 1 && (
                                    <RadioGroup
                                        value={active?.key ?? ''}
                                        onValueChange={setActiveKey}
                                        aria-label="Payment method"
                                        className="mb-5 grid gap-2.5 sm:grid-cols-2"
                                    >
                                        {gateways.map((g) => {
                                            const selected =
                                                active?.key === g.key;
                                            const id = `gateway-${g.key}`;

                                            return (
                                                <Label
                                                    key={g.key}
                                                    htmlFor={id}
                                                    className={cn(
                                                        'group relative flex cursor-pointer items-center gap-3 rounded-xl border bg-card p-3.5 text-left transition-all duration-200',
                                                        selected
                                                            ? 'border-primary shadow-card ring-2 ring-primary/25'
                                                            : 'border-border hover:border-primary/40 hover:bg-muted/30',
                                                    )}
                                                >
                                                    <RadioGroupItem
                                                        id={id}
                                                        value={g.key}
                                                        className="size-[18px] border-2 border-muted-foreground/30 group-hover:border-primary/50 data-[state=checked]:border-primary"
                                                    />
                                                    <span className="flex min-w-0 flex-1 flex-col gap-1.5">
                                                        <span className="text-xs font-medium text-muted-foreground">
                                                            {g.title}
                                                        </span>
                                                        <GatewayBrand
                                                            gateway={g}
                                                        />
                                                    </span>
                                                </Label>
                                            );
                                        })}
                                    </RadioGroup>
                                )}

                                {gateways.length === 0 ? (
                                    <div className="rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
                                        Payment is temporarily unavailable.
                                        Please try again shortly, or contact us
                                        and we'll complete your booking
                                        directly.
                                    </div>
                                ) : !active ? (
                                    <div className="flex flex-col items-center gap-2 py-12 text-center text-sm text-muted-foreground">
                                        <CreditCard className="size-6 opacity-60" />
                                        {gateways.length === 1 ? (
                                            <Button
                                                type="button"
                                                className="mt-2 gap-2"
                                                onClick={() =>
                                                    setActiveKey(
                                                        gateways[0].key,
                                                    )
                                                }
                                            >
                                                <CreditCard className="size-4" />
                                                {gateways[0].title}
                                            </Button>
                                        ) : (
                                            'Choose a payment method above to continue.'
                                        )}
                                    </div>
                                ) : active.mode === modes.embedded ? (
                                    <StripeEmbeddedCheckout gateway={active} />
                                ) : (
                                    <PayPalCheckout gateway={active} />
                                )}
                            </div>

                            <div className="flex flex-col items-center gap-2 border-t bg-muted/30 px-5 py-3.5 text-xs text-muted-foreground">
                                <span className="flex items-center gap-1.5">
                                    <ShieldCheck className="size-3.5 text-primary" />
                                    Payments processed by Stripe &amp; PayPal
                                </span>
                                <span className="flex items-center gap-2">
                                    <CardBrands />
                                    <PaypalMark />
                                </span>
                            </div>
                        </motion.section>

                        {/* Order summary — plan-based or custom offer */}
                        <motion.aside
                            className="relative order-1 h-fit p-3 lg:sticky lg:top-8 lg:order-2"
                            {...fade(0.12)}
                        >
                            <CropMarks />
                            <div className="overflow-hidden surface-card">
                                <div className="border-b px-6 py-4">
                                    <p className="text-microcaps text-brand-accent">
                                        Order summary
                                    </p>
                                </div>
                                <div className="p-6">
                                    <div className="flex items-start justify-between gap-3">
                                        <h3 className="font-serif text-lg font-semibold">
                                            {plan ? plan.name : offer.title}
                                        </h3>
                                        {plan?.is_featured && (
                                            <span className="inline-flex shrink-0 items-center gap-1 rounded-full border border-primary/40 bg-primary/10 px-2 py-0.5 text-microcaps text-brand-accent">
                                                <span aria-hidden>✦</span> Most
                                                engaged
                                            </span>
                                        )}
                                    </div>
                                    <div className="mt-2 flex items-baseline gap-1.5">
                                        <span className="font-mono text-2xl font-bold tracking-tight tabular-nums">
                                            {priceLabel}
                                        </span>
                                        {plan && (
                                            <span className="text-sm text-muted-foreground">
                                                {plan.interval_label}
                                            </span>
                                        )}
                                    </div>
                                    {(plan?.description ??
                                        offer.description) && (
                                        <p className="mt-3 text-sm text-muted-foreground">
                                            {plan?.description ??
                                                offer.description}
                                        </p>
                                    )}

                                    {quotation && (
                                        <>
                                            <p className="mt-3 text-xs text-muted-foreground">
                                                Quotation{' '}
                                                <span className="font-mono font-medium text-foreground">
                                                    {quotation.number}
                                                </span>
                                                {quotation.validUntil && (
                                                    <>
                                                        {' '}
                                                        · valid until{' '}
                                                        {quotation.validUntil}
                                                    </>
                                                )}
                                            </p>

                                            {quotation.scopeItems.length >
                                                0 && (
                                                <>
                                                    <p className="mt-6 text-microcaps text-muted-foreground">
                                                        Scope of services
                                                    </p>
                                                    <ul className="mt-2">
                                                        {quotation.scopeItems.map(
                                                            (item) => (
                                                                <li
                                                                    key={item}
                                                                    className="flex items-start gap-3 border-b border-border/60 py-2.5 text-sm last:border-0"
                                                                >
                                                                    <span
                                                                        aria-hidden
                                                                        className="mt-1 text-[10px] text-primary"
                                                                    >
                                                                        ✦
                                                                    </span>
                                                                    <span>
                                                                        {item}
                                                                    </span>
                                                                </li>
                                                            ),
                                                        )}
                                                    </ul>
                                                </>
                                            )}

                                            <p className="mt-6 text-microcaps text-muted-foreground">
                                                Line items
                                            </p>
                                            <div className="mt-1">
                                                <QuotationLines
                                                    items={quotation.lineItems}
                                                    variant="list"
                                                />
                                            </div>
                                        </>
                                    )}

                                    {plan && plan.features.length > 0 && (
                                        <>
                                            <p className="mt-6 text-microcaps text-muted-foreground">
                                                What's included
                                            </p>
                                            <ul className="mt-2">
                                                {plan.features.map(
                                                    (feature) => (
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
                                                            <span>
                                                                {feature}
                                                            </span>
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        </>
                                    )}

                                    {plan?.outcome && (
                                        <p className="mt-5 border-l-2 border-primary/50 pl-4 font-serif text-sm text-muted-foreground italic">
                                            “{plan.outcome}”
                                        </p>
                                    )}
                                </div>

                                <div className="border-t-[3px] border-double bg-muted/30 px-6 py-4">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-medium">
                                            Total due today
                                        </span>
                                        <span className="font-mono text-lg font-bold tabular-nums">
                                            {priceLabel}
                                        </span>
                                    </div>
                                    <p className="mt-1.5 text-xs text-muted-foreground">
                                        One-time payment — you won't be charged
                                        again automatically.
                                    </p>
                                </div>
                            </div>
                        </motion.aside>
                    </div>
                </div>
            </div>
        </>
    );
}
