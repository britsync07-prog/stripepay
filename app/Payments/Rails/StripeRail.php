<?php

namespace App\Payments\Rails;

use App\Enums\PayoutStatus;
use App\Models\Expert;
use App\Payments\Contracts\PayoutRail;
use App\Payments\PayoutResult;
use App\Services\Payment\StripeConnect;

/**
 * Stripe Connect (Express) payout rail. Unlike batch rails, a Stripe transfer to
 * the connected account settles synchronously — so a sent payout is immediately
 * Paid; there is no async settlement to poll.
 */
class StripeRail implements PayoutRail
{
    public function key(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return 'Stripe';
    }

    public function isConfigured(): bool
    {
        return filled(config('services.stripe.secret'));
    }

    public function ensureRecipient(Expert $expert): string
    {
        return app(StripeConnect::class)->ensureAccount($expert);
    }

    public function onboardingUrl(Expert $expert): string
    {
        $connect = app(StripeConnect::class);
        $connect->ensureAccount($expert);

        return $connect->onboardingLink(
            $expert,
            returnUrl: route('admin.experts.stripe.return', $expert),
            refreshUrl: route('admin.experts.stripe.refresh', $expert),
        );
    }

    public function recipientReady(Expert $expert): bool
    {
        if (! $expert->stripe_account_id) {
            return false;
        }

        return app(StripeConnect::class)->refreshStatus($expert);
    }

    public function pay(Expert $expert, int $amountMinor, string $currency, string $idempotencyKey, ?string $memo = null): PayoutResult
    {
        $transfer = app(StripeConnect::class)->transfer(
            $expert,
            $amountMinor,
            $currency,
            $memo ?? 'Consultation payout',
            $idempotencyKey,
        );

        // A Connect transfer is synchronous — the money is in the expert's Stripe
        // account the moment this returns. Mark it paid.
        return new PayoutResult(
            reference: $transfer->id,
            status: 'paid',
            raw: ['transfer_id' => $transfer->id],
        );
    }

    /** Transfers settle synchronously — nothing to poll. */
    public function paymentStatus(string $reference): ?PayoutStatus
    {
        return null;
    }
}
