import { Link, router } from '@inertiajs/react';
import { Mail, MapPin, Pencil, Wallet } from 'lucide-react';

import { PayoutReadiness } from '@/components/expert/payout-readiness';
import { Reveal } from '@/components/reveal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { edit } from '@/routes/admin/experts';
import { onboard } from '@/routes/admin/experts/payout';
import type { Expert } from '@/types';

function initialsOf(name: string) {
    return (
        name
            .split(/\s+/)
            .map((w) => w[0])
            .filter(Boolean)
            .slice(0, 2)
            .join('')
            .toUpperCase() || '?'
    );
}

export function ExpertHero({ expert }: { expert: Expert }) {
    return (
        <Reveal>
            <div className="overflow-hidden surface-card">
                <div className="flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-4">
                        <span className="flex size-16 shrink-0 items-center justify-center rounded-xl bg-muted font-serif text-xl font-semibold text-foreground ring-1 ring-border ring-inset">
                            {initialsOf(expert.name)}
                        </span>
                        <div className="min-w-0">
                            <p className="text-[0.7rem] font-medium tracking-wide text-primary/80 uppercase">
                                Expert
                            </p>
                            <h1 className="truncate font-serif text-2xl font-semibold">
                                {expert.name}
                            </h1>
                            <p className="truncate text-sm text-muted-foreground">
                                {expert.specialization ??
                                    'No specialization set'}
                            </p>
                            <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
                                <span className="inline-flex items-center gap-1.5">
                                    <Mail className="size-3.5" />
                                    {expert.email ?? '—'}
                                </span>
                                {expert.country && (
                                    <span className="inline-flex items-center gap-1.5">
                                        <MapPin className="size-3.5" />
                                        {expert.country}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-col gap-3 sm:items-end">
                        <div className="flex flex-wrap gap-2">
                            {expert.is_active ? (
                                <Badge variant="success">Active</Badge>
                            ) : (
                                <Badge variant="secondary">Inactive</Badge>
                            )}
                            <PayoutReadiness expert={expert} />
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    router.post(onboard(expert.id).url)
                                }
                            >
                                <Wallet className="size-4" />
                                {expert.stripe_payouts_enabled
                                    ? 'Re-open onboarding'
                                    : 'Set up payouts'}
                            </Button>
                            <Button asChild variant="outline" size="sm">
                                <Link href={edit(expert.id)}>
                                    <Pencil className="size-4" /> Edit
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>

                {(expert.stripe_account_id || expert.payout_notes) && (
                    <div className="flex flex-col gap-2 border-t bg-muted/30 px-6 py-3 text-sm sm:flex-row sm:items-center sm:gap-6">
                        {expert.stripe_account_id && (
                            <span className="inline-flex items-center gap-2 text-muted-foreground">
                                <Wallet className="size-3.5" />
                                Stripe account
                                <span className="font-mono text-xs text-foreground">
                                    {expert.stripe_account_id}
                                </span>
                            </span>
                        )}
                        {expert.payout_notes && (
                            <span className="truncate text-muted-foreground">
                                {expert.payout_notes}
                            </span>
                        )}
                    </div>
                )}
            </div>
        </Reveal>
    );
}
