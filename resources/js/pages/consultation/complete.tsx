import { Head, Link } from '@inertiajs/react';
import {
    CheckCircle2,
    Clock,
    LayoutDashboard,
    LogIn,
    Mail,
    ShieldCheck,
} from 'lucide-react';
import { motion, useReducedMotion } from 'motion/react';

import { CropMarks } from '@/components/guest/crop-marks';
import { GuestTopbar } from '@/components/guest/guest-topbar';
import { Button } from '@/components/ui/button';
import { EASE_SPRING } from '@/constants/motion';
import { cn } from '@/lib/utils';
import { login } from '@/routes';
import { request as forgotPassword } from '@/routes/password';

interface Props {
    request: {
        name: string;
        email: string;
        status: string;
        status_label: string;
    };
    plan: { name: string; formatted_price: string } | null;
    isAuthenticated: boolean;
    portalUrl: string;
}

export default function Complete({
    request,
    plan,
    isAuthenticated,
    portalUrl,
}: Props) {
    const reduce = useReducedMotion();
    const paid = request.status === 'paid';

    const fade = (delay = 0) => ({
        initial: { opacity: 0, y: reduce ? 0 : 16 },
        animate: { opacity: 1, y: 0 },
        transition: { duration: 0.55, ease: EASE_SPRING, delay },
    });

    return (
        <>
            <Head title={paid ? 'Payment confirmed' : 'Request received'} />
            <div className="min-h-screen bg-background text-foreground">
                <GuestTopbar step={paid ? 3 : 2} backLabel="Back to the firm" />

                <div className="mx-auto w-full max-w-xl px-6 py-14 sm:py-18">
                    {/* Status header */}
                    <motion.div className="text-center" {...fade()}>
                        <span
                            className={cn(
                                'relative mx-auto flex size-18 items-center justify-center rounded-full border-2 border-dashed',
                                paid
                                    ? 'border-success/40 bg-success/5'
                                    : 'border-warning/40 bg-warning/5',
                            )}
                        >
                            {!reduce && (
                                <span
                                    className={cn(
                                        'absolute inline-flex h-full w-full animate-ping rounded-full',
                                        paid
                                            ? 'bg-success/15'
                                            : 'bg-warning/15',
                                    )}
                                    style={{ animationIterationCount: 3 }}
                                />
                            )}
                            {paid ? (
                                <CheckCircle2 className="relative size-9 text-success" />
                            ) : (
                                <Clock className="relative size-9 text-warning" />
                            )}
                        </span>

                        <p className="mt-6 flex items-center justify-center gap-4 text-kicker text-primary">
                            <span
                                aria-hidden
                                className="h-px w-10 bg-gradient-to-r from-transparent to-primary/50"
                            />
                            {paid ? 'Confirmation — 03' : 'Request received'}
                            <span
                                aria-hidden
                                className="h-px w-10 bg-gradient-to-l from-transparent to-primary/50"
                            />
                        </p>

                        <h1 className="mt-4 font-serif text-3xl leading-[1.08] font-semibold tracking-tight text-balance sm:text-4xl">
                            {paid ? (
                                <>
                                    Thank you —{' '}
                                    <em className="text-gold-gradient">
                                        you're all set
                                    </em>
                                </>
                            ) : (
                                'Almost there'
                            )}
                        </h1>

                        <p className="mx-auto mt-3 max-w-md text-muted-foreground">
                            {paid ? (
                                <>
                                    {request.name
                                        ? `${request.name}, your`
                                        : 'Your'}{' '}
                                    payment is confirmed and your consultation
                                    is now being prepared.
                                </>
                            ) : (
                                <>
                                    Your request is saved (
                                    {request.status_label}). If you didn't
                                    finish payment, head back to the plan and
                                    try again.
                                </>
                            )}
                        </p>
                    </motion.div>

                    {/* Receipt */}
                    {paid && plan && (
                        <motion.div
                            className="relative mt-9 p-3"
                            {...fade(0.08)}
                        >
                            <CropMarks />
                            <div className="overflow-hidden surface-card">
                                <div className="flex items-center justify-between border-b bg-gradient-to-b from-muted/50 to-transparent px-5 py-3.5">
                                    <p className="text-microcaps text-brand-accent">
                                        Receipt
                                    </p>
                                    <span className="inline-flex items-center gap-1.5 rounded-full border border-success/30 bg-success/10 px-2.5 py-0.5 text-microcaps text-success">
                                        <span aria-hidden>✦</span> Paid
                                    </span>
                                </div>
                                <div className="p-5">
                                    <div className="flex items-center justify-between gap-4">
                                        <span className="font-serif text-base font-semibold">
                                            {plan.name}
                                        </span>
                                        <span className="font-mono text-base font-bold tabular-nums">
                                            {plan.formatted_price}
                                        </span>
                                    </div>
                                    <div className="mt-4 flex items-center justify-between gap-4 border-t-[3px] border-double pt-3 text-sm">
                                        <span className="text-muted-foreground">
                                            Receipt sent to
                                        </span>
                                        <span className="truncate font-medium">
                                            {request.email}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </motion.div>
                    )}

                    {/* Next step */}
                    <motion.div className="mt-6" {...fade(0.12)}>
                        {paid && isAuthenticated ? (
                            <div className="space-y-3">
                                <div className="rounded-xl border bg-muted/50 p-4 text-left text-sm">
                                    <p className="font-medium text-foreground">
                                        Your client account is ready
                                    </p>
                                    <p className="mt-1 text-muted-foreground">
                                        You're signed in — track this
                                        consultation anytime in your dashboard.
                                        We also emailed{' '}
                                        <span className="font-medium text-foreground">
                                            {request.email}
                                        </span>{' '}
                                        a one-click link to get back in from any
                                        device.
                                    </p>
                                </div>
                                <Button asChild size="lg" className="w-full">
                                    <Link href={portalUrl}>
                                        <LayoutDashboard className="size-4" />{' '}
                                        Go to my dashboard
                                    </Link>
                                </Button>
                            </div>
                        ) : paid ? (
                            <div className="space-y-3">
                                <div className="flex items-start gap-2.5 rounded-xl border bg-muted/50 p-4 text-left text-sm">
                                    <Mail className="mt-0.5 size-4 shrink-0 text-primary" />
                                    <p className="text-muted-foreground">
                                        We've emailed a secure one-click link to{' '}
                                        <span className="font-medium text-foreground">
                                            {request.email}
                                        </span>
                                        . Open it to go straight to your
                                        dashboard — no password needed.
                                    </p>
                                </div>
                                <Button
                                    asChild
                                    size="lg"
                                    variant="outline"
                                    className="w-full"
                                >
                                    <Link href={login()}>
                                        <LogIn className="size-4" /> Or log in
                                        with a password
                                    </Link>
                                </Button>
                                <Link
                                    href={forgotPassword()}
                                    className="block text-center text-xs text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                                >
                                    Set or reset your password
                                </Link>
                            </div>
                        ) : (
                            <Button asChild size="lg" className="w-full">
                                <Link href="/">Back to engagements</Link>
                            </Button>
                        )}
                    </motion.div>

                    {/* Trust footer */}
                    <motion.div
                        className="mt-9 flex items-center justify-center gap-1.5 text-microcaps text-muted-foreground"
                        {...fade(0.16)}
                    >
                        <ShieldCheck className="size-3.5 text-primary" />
                        Encrypted &amp; processed securely
                    </motion.div>
                </div>
            </div>
        </>
    );
}
