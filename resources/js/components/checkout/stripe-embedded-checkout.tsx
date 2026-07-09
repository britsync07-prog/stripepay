import {
    EmbeddedCheckout,
    EmbeddedCheckoutProvider,
} from '@stripe/react-stripe-js';
import { loadStripe } from '@stripe/stripe-js';
import { useEffect, useMemo, useState } from 'react';

import { Spinner } from '@/components/ui/spinner';
import { postJson } from '@/lib/checkout';
import type { Gateway } from '@/types';

/** Stripe Embedded Checkout — fetches its session only once this mounts (i.e. on select). */
export function StripeEmbeddedCheckout({ gateway }: { gateway: Gateway }) {
    const [clientSecret, setClientSecret] = useState<string | null>(null);
    const [failed, setFailed] = useState(false);

    const stripePromise = useMemo(
        () =>
            gateway.publishableKey ? loadStripe(gateway.publishableKey) : null,
        [gateway.publishableKey],
    );

    useEffect(() => {
        let cancelled = false;

        postJson(gateway.sessionUrl)
            .then((data) => {
                if (cancelled) {
                    return;
                }

                if (data.clientSecret) {
                    setClientSecret(data.clientSecret);
                } else {
                    setFailed(true);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setFailed(true);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [gateway.sessionUrl]);

    if (failed) {
        return (
            <div className="rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
                Card checkout couldn't be started. Please try again.
            </div>
        );
    }

    if (!clientSecret || !stripePromise) {
        return (
            <div className="flex items-center justify-center gap-2 py-12 text-sm text-muted-foreground">
                <Spinner /> Loading secure card form…
            </div>
        );
    }

    return (
        <EmbeddedCheckoutProvider
            stripe={stripePromise}
            options={{ clientSecret }}
        >
            <EmbeddedCheckout />
        </EmbeddedCheckoutProvider>
    );
}
