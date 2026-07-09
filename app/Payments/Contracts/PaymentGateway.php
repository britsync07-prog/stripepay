<?php

namespace App\Payments\Contracts;

use App\Models\ConsultationRequest;
use App\Models\Payment;
use App\Payments\CheckoutSession;
use App\Payments\CheckoutUrls;
use App\Payments\VerifiedPayment;

/**
 * A payment rail. Implementations adapt one provider's API to this contract;
 * the rest of the app never names a concrete gateway. Adding a rail = a new
 * implementation + a config key — nothing else changes.
 */
interface PaymentGateway
{
    /** Render mode: an inline embedded form (Stripe). */
    public const MODE_EMBEDDED = 'embedded';

    /** Render mode: provider SDK buttons (PayPal). */
    public const MODE_BUTTONS = 'buttons';

    /** Stable machine key, e.g. 'stripe'. */
    public function key(): string;

    /** Buyer-facing label, e.g. 'Card' or 'PayPal'. */
    public function label(): string;

    /** Buyer-facing checkout-option title, e.g. 'Pay by card'. */
    public function title(): string;

    /** How the frontend renders it: one of the MODE_* constants. */
    public function mode(): string;

    public function isConfigured(): bool;

    /**
     * Public, frontend-safe config the gateway's SDK needs (e.g. a publishable
     * client id, currency). Never secrets. Empty for gateways that don't need it.
     *
     * @return array<string, string|null>
     */
    public function publicConfig(ConsultationRequest $request): array;

    /** Begin a checkout and return what the frontend needs to proceed. */
    public function begin(ConsultationRequest $request, CheckoutUrls $urls): CheckoutSession;

    /** Verify a return callback (query params) and normalize it, or null if unpaid. */
    public function verify(ConsultationRequest $request, array $callback): ?VerifiedPayment;

    /** Hook run once a payment has been recorded, for any gateway-specific follow-up. */
    public function afterRecorded(Payment $payment): void;
}
