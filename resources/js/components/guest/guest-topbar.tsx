import { Link } from '@inertiajs/react';
import { ArrowLeft, Check, Lock } from 'lucide-react';

import { cn } from '@/lib/utils';

const STEPS = ['Details', 'Payment', 'Confirmation'] as const;

export function GuestTopbar({
    step,
    backHref = '/',
    backLabel = 'Return to the firm',
}: {
    step?: 1 | 2 | 3;
    backHref?: string;
    backLabel?: string;
}) {
    return (
        <header className="relative border-b border-border/70 bg-background/90 backdrop-blur-xl">
            <div
                aria-hidden
                className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent"
            />
            <div className="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-6 py-4">
                <Link
                    href={backHref}
                    className="group inline-flex shrink-0 items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft className="size-4 transition-transform group-hover:-translate-x-0.5" />
                    <span className="hidden sm:inline">{backLabel}</span>
                    <span className="sm:hidden">Back</span>
                </Link>

                {step && (
                    <ol
                        aria-label="Checkout progress"
                        className="hidden items-center md:flex"
                    >
                        {STEPS.map((label, i) => {
                            const n = (i + 1) as 1 | 2 | 3;
                            const done = n < step;
                            const current = n === step;

                            return (
                                <li
                                    key={label}
                                    aria-current={current ? 'step' : undefined}
                                    className="flex items-center"
                                >
                                    <span
                                        className={cn(
                                            'text-kicker',
                                            done || current
                                                ? 'text-primary'
                                                : 'text-muted-foreground/50',
                                        )}
                                    >
                                        {done ? (
                                            <Check
                                                aria-hidden
                                                className="size-3.5"
                                            />
                                        ) : (
                                            `0${n}`
                                        )}
                                    </span>
                                    <span
                                        className={cn(
                                            'ml-2 text-xs tracking-wide',
                                            current
                                                ? 'font-semibold text-foreground'
                                                : 'text-muted-foreground',
                                        )}
                                    >
                                        {label}
                                    </span>
                                    {i < STEPS.length - 1 && (
                                        <span
                                            aria-hidden
                                            className={cn(
                                                'mx-4 h-px w-10',
                                                done
                                                    ? 'bg-primary/50'
                                                    : 'bg-border',
                                            )}
                                        />
                                    )}
                                </li>
                            );
                        })}
                    </ol>
                )}

                <span className="flex shrink-0 items-center gap-1.5 text-microcaps text-muted-foreground">
                    <Lock className="size-3.5 text-primary" /> Secured
                </span>
            </div>
        </header>
    );
}
