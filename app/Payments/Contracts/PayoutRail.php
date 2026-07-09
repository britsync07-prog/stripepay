<?php

namespace App\Payments\Contracts;

use App\Enums\PayoutStatus;
use App\Models\Expert;
use App\Payments\PayoutResult;

/**
 * A money-out rail (the payout counterpart to {@see PaymentGateway}). One adapter
 * per provider (Stripe, …); the rest of the app resolves rails by key and
 * never names a concrete provider. Adding a rail = an adapter + a config key.
 */
interface PayoutRail
{
    public function key(): string;

    public function label(): string;

    public function isConfigured(): bool;

    /** Create or return the provider recipient id for this expert. */
    public function ensureRecipient(Expert $expert): string;

    /** Hosted onboarding URL where the expert enters payout details + tax forms. */
    public function onboardingUrl(Expert $expert): string;

    /** Whether the expert can be paid (recipient onboarded + payable). */
    public function recipientReady(Expert $expert): bool;

    /** Send a payout. Idempotent on $idempotencyKey. */
    public function pay(Expert $expert, int $amountMinor, string $currency, string $idempotencyKey, ?string $memo = null): PayoutResult;

    /** Re-fetch a sent payout's status by its provider reference, mapped to our status (null if unknown). */
    public function paymentStatus(string $reference): ?PayoutStatus;
}
