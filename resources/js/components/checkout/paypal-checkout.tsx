import { PayPalButtons, PayPalScriptProvider } from '@paypal/react-paypal-js';
import { toast } from 'sonner';

import { postJson } from '@/lib/checkout';
import type { Gateway } from '@/types';

/** PayPal Smart Buttons — creates the order, captures on approve, then redirects. */
export function PayPalCheckout({ gateway }: { gateway: Gateway }) {
    if (!gateway.clientId || !gateway.captureUrl) {
        return (
            <div className="rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
                {gateway.label} checkout couldn't be started. Please try again.
            </div>
        );
    }

    const captureUrl = gateway.captureUrl;

    return (
        <PayPalScriptProvider
            options={{
                clientId: gateway.clientId,
                currency: gateway.currency ?? 'USD',
                intent: 'capture',
            }}
        >
            <PayPalButtons
                style={{ layout: 'vertical', shape: 'rect' }}
                createOrder={async () => {
                    const data = await postJson(gateway.sessionUrl);

                    if (!data.id) {
                        throw new Error(
                            data.message ??
                                `Could not start payment (HTTP ${data.httpStatus}). Reload this page and try again.`,
                        );
                    }

                    return data.id;
                }}
                onApprove={async (data) => {
                    const result = await postJson(captureUrl, {
                        orderID: data.orderID,
                    });

                    if (result.ok && result.redirect) {
                        window.location.href = result.redirect;

                        return;
                    }

                    toast.error(
                        result.message ??
                            `Payment could not be completed (HTTP ${result.httpStatus}).`,
                    );
                }}
                onError={(err) => {
                    console.error('[checkout] PayPal error', err);
                    toast.error(
                        err instanceof Error && err.message
                            ? err.message
                            : 'Something went wrong with PayPal. Please try again.',
                    );
                }}
            />
        </PayPalScriptProvider>
    );
}
