<?php

namespace App\Payments\Gateways;

use App\Actions\Payments\BackfillStripeFee;
use App\Models\ConsultationRequest;
use App\Models\Payment;
use App\Payments\CheckoutSession;
use App\Payments\CheckoutUrls;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\VerifiedPayment;
use App\Services\Payment\StripeCheckout;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;

/** Stripe Embedded Checkout adapter. Wraps the low-level {@see StripeCheckout}. */
class StripeGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return 'Card';
    }

    public function title(): string
    {
        return 'Pay by card';
    }

    public function mode(): string
    {
        return self::MODE_EMBEDDED;
    }

    public function isConfigured(): bool
    {
        return filled(config('services.stripe.secret')) && filled(config('services.stripe.key'));
    }

    public function publicConfig(ConsultationRequest $request): array
    {
        // Only the public key here; the per-session clientSecret comes from begin().
        return ['publishableKey' => (string) config('services.stripe.key')];
    }

    public function begin(ConsultationRequest $request, CheckoutUrls $urls): CheckoutSession
    {
        $checkout = app(StripeCheckout::class);
        $session = $this->reuseOpenSession($checkout, $request);

        if (! $session) {
            $returnUrl = $urls->returnUrl.'?session_id={CHECKOUT_SESSION_ID}';
            $session = $checkout->createSession($request, $returnUrl);
            $request->update([
                'checkout_gateway' => $this->key(),
                'checkout_reference' => $session->id,
            ]);
        }

        return new CheckoutSession(
            mode: $this->mode(),
            reference: $session->id,
            clientSecret: $session->client_secret,
        );
    }

    public function verify(ConsultationRequest $request, array $callback): ?VerifiedPayment
    {
        $sessionId = (string) ($callback['session_id'] ?? '');

        if ($sessionId === '') {
            return null;
        }

        $session = app(StripeCheckout::class)->retrieveSession(
            $sessionId,
            ['payment_intent.latest_charge.balance_transaction'],
        );

        // Fail closed: the session must belong to THIS consultation. Without this
        // a leaked/known session id from a same-priced consultation could be
        // replayed against another one (the amount guard alone wouldn't catch it).
        if ((string) ($session->metadata['consultation_request_uuid'] ?? '') !== $request->uuid) {
            Log::warning('[payment] stripe session rejected (binding mismatch)', [
                'consultation' => $request->uuid,
                'session_id' => $sessionId,
            ]);

            return null;
        }

        if ($session->payment_status !== 'paid') {
            return null;
        }

        $details = $session->customer_details;

        // The payment intent may be a bare id or, when expanded, a full object.
        $pi = $session->payment_intent;
        $paymentIntentId = is_string($pi) ? $pi : ($pi->id ?? null);

        [$feeMinor, $netMinor] = self::feeNet($session);

        return new VerifiedPayment(
            reference: $session->id,
            amountMinor: (int) $session->amount_total,
            currency: strtoupper((string) $session->currency),
            payerName: $details->name ?? null,
            payerEmail: $details->email ?? null,
            payerPhone: $details->phone ?? null,
            payerCountry: $details->address->country ?? null,
            feeMinor: $feeMinor,
            netMinor: $netMinor,
            raw: [
                'transaction_id' => $paymentIntentId,
                'payment_intent' => $paymentIntentId,
                'payment_status' => $session->payment_status,
                'customer_email' => $details->email ?? null,
            ],
        );
    }

    public function afterRecorded(Payment $payment): void
    {
        if ($payment->fee === null) {
            BackfillStripeFee::dispatch($payment)->delay(now()->addSeconds(60));
        }
    }

    public static function feeNet(Session $session): array
    {
        $pi = $session->payment_intent;
        $charge = is_object($pi) && is_object($pi->latest_charge ?? null) ? $pi->latest_charge : null;
        $bt = $charge && is_object($charge->balance_transaction ?? null) ? $charge->balance_transaction : null;

        if (! $bt || ! isset($bt->fee, $bt->net)) {
            return [null, null];
        }

        $fee = (int) $bt->fee;
        $net = (int) $bt->net;

        $settlement = strtolower((string) ($bt->currency ?? $session->currency));
        $charged = strtolower((string) $session->currency);
        $rate = $bt->exchange_rate ?? null;

        if ($settlement !== $charged && $rate) {
            $fee = (int) round($fee / $rate);
            $net = (int) round($net / $rate);
        }

        return [$fee, $net];
    }

    private function reuseOpenSession(StripeCheckout $checkout, ConsultationRequest $request): ?Session
    {
        if ($request->checkout_gateway !== $this->key() || ! $request->checkout_reference) {
            return null;
        }

        try {
            $session = $checkout->retrieveSession($request->checkout_reference);
        } catch (\Throwable) {
            return null;
        }

        if ($session->status !== 'open') {
            return null;
        }

        return $this->hasCurrentRiskControls($session, $request) ? $session : null;
    }

    private function hasCurrentRiskControls(Session $session, ConsultationRequest $request): bool
    {
        $expectedRelationship = $request->isPaymentLink() ? 'known_by_admin' : 'standard';
        $expectedAuthentication = 'automatic';
        $expectedThreeDSecure = 'automatic';

        return (string) ($session->metadata['consultation_request_uuid'] ?? '') === $request->uuid
            && (string) ($session->metadata['client_relationship'] ?? '') === $expectedRelationship
            && (string) ($session->metadata['strong_customer_authentication'] ?? '') === $expectedAuthentication
            && (string) ($session->payment_method_options->card->request_three_d_secure ?? '') === $expectedThreeDSecure
            && (string) ($session->customer_creation ?? '') === 'always';
    }
}
