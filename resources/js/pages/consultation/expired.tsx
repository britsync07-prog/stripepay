import { Head, Link } from '@inertiajs/react';
import { Clock } from 'lucide-react';

import { CropMarks } from '@/components/guest/crop-marks';
import { GuestTopbar } from '@/components/guest/guest-topbar';
import { Button } from '@/components/ui/button';

interface Props {
    offer: { title: string; amount: string };
}

export default function CheckoutExpired({ offer }: Props) {
    return (
        <>
            <Head title="Payment link expired" />
            <div className="min-h-screen bg-background text-foreground">
                <GuestTopbar backLabel="Return to the firm" />

                <div className="mx-auto flex max-w-md flex-col items-center px-6 py-20 text-center">
                    <span className="flex size-16 items-center justify-center rounded-full border-2 border-dashed border-warning/40 bg-warning/5 text-warning">
                        <Clock className="size-6" />
                    </span>
                    <p className="mt-6 flex items-center gap-4 text-kicker text-primary">
                        <span
                            aria-hidden
                            className="h-px w-10 bg-gradient-to-r from-transparent to-primary/50"
                        />
                        Link expired
                        <span
                            aria-hidden
                            className="h-px w-10 bg-gradient-to-l from-transparent to-primary/50"
                        />
                    </p>
                    <h1 className="mt-4 font-serif text-2xl leading-[1.1] font-semibold tracking-tight sm:text-3xl">
                        This payment link has{' '}
                        <em className="text-gold-gradient">expired</em>
                    </h1>

                    <div className="relative mt-8 w-full p-3">
                        <CropMarks />
                        <div className="surface-card p-5">
                            <div className="flex items-center justify-between gap-4">
                                <span className="font-serif text-base font-semibold">
                                    {offer.title}
                                </span>
                                <span className="font-mono text-base font-bold tabular-nums">
                                    {offer.amount}
                                </span>
                            </div>
                            <p className="mt-3 border-t border-border/60 pt-3 text-sm text-muted-foreground">
                                This link is no longer valid. Contact us and
                                we'll send you a fresh one right away.
                            </p>
                        </div>
                    </div>

                    <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                        <Button asChild>
                            <Link href="/">Return home</Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link href="/custom-plan">Request a new plan</Link>
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}
