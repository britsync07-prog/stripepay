<?php

namespace App\Actions\Concerns;

use App\Enums\PayoutStatus;
use App\Models\Expert;
use App\Models\Payout;
use App\Payments\PayoutRailManager;
use Illuminate\Support\Facades\Cache;

/**
 * Keeps payout/recipient status fresh from the rail on admin screen loads, so
 * the UI is correct even when webhooks aren't set up or haven't fired yet.
 * Everything is guarded — a rail hiccup logs and is skipped, never breaking the
 * page — and already-settled rows make no API call.
 */
trait RefreshesPayoutStatus
{
    /** How long to trust a recipient-readiness check before re-hitting the rail. */
    protected int $payoutRefreshThrottleMinutes = 10;

    /** Re-check the rail for any expert mid-onboarding and store the latest status. */
    protected function refreshPendingPayoutStatus(iterable $experts, PayoutRailManager $rails): void
    {
        $rail = $rails->rail($rails->getDefaultDriver());

        if (! $rail->isConfigured()) {
            return;
        }

        foreach ($experts as $expert) {
            if (! $expert instanceof Expert
                || ! $expert->stripe_account_id
                || $expert->stripe_payouts_enabled) {
                continue;
            }

            // Throttle: each connected account is re-checked at most once per
            // window, so a page full of mid-onboarding experts can't fan out
            // into one blocking Stripe API call per row on every load.
            // Cache::add() only succeeds when the key is absent (first hit or
            // after the window has elapsed).
            if (! Cache::add(
                "payout-recipient-check:{$expert->id}",
                true,
                now()->addMinutes($this->payoutRefreshThrottleMinutes),
            )) {
                continue;
            }

            rescue(fn () => $rail->recipientReady($expert), report: true);
        }
    }

    /** Re-check the rail for any still-Processing payout and advance it to Paid/Failed. */
    protected function refreshProcessingPayouts(iterable $payouts, PayoutRailManager $rails): void
    {
        foreach ($payouts as $payout) {
            if (! $payout instanceof Payout
                || $payout->status !== PayoutStatus::Processing
                || blank($payout->reference)) {
                continue;
            }

            $rail = rescue(fn () => $rails->rail($payout->method->value), report: false);

            if (! $rail || ! $rail->isConfigured()) {
                continue;
            }

            rescue(function () use ($payout, $rail) {
                $status = $rail->paymentStatus($payout->reference);

                if ($status === null || $status === $payout->status) {
                    return;
                }

                $payout->update([
                    'status' => $status,
                    'paid_at' => $status === PayoutStatus::Paid ? now() : $payout->paid_at,
                ]);

                activity('payout')
                    ->performedOn($payout)
                    ->withProperties(['status' => $status->value])
                    ->log('Payout status refreshed from rail');
            }, report: true);
        }
    }
}
