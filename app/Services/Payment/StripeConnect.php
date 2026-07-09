<?php

namespace App\Services\Payment;

use App\Models\Expert;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Transfer;

/**
 * Stripe Connect (Express) wrapper — the only place that talks to the Connect
 * API. Creates a connected account per expert, generates hosted onboarding
 * links, reads payout readiness, and transfers money to the expert's account.
 * The expert never needs a Stripe login; Express hosts the onboarding.
 */
class StripeConnect
{
    public function __construct(private readonly StripeClient $stripe) {}

    public static function make(): self
    {
        return new self(new StripeClient((string) config('services.stripe.secret')));
    }

    /**
     * Run a Stripe SDK call while swallowing the non-fatal `stripe-notice`
     * deprecation warning the SDK raises via trigger_error(E_USER_WARNING).
     * That notice fires AFTER the API call already succeeded; Laravel would
     * otherwise convert it to a fatal ErrorException and orphan the new account.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function withoutNoticeWarnings(callable $callback)
    {
        set_error_handler(static function (int $level, string $message): bool {
            Log::info('Stripe notice: '.$message);

            return true; // handled — do not escalate to an ErrorException
        }, E_USER_WARNING);

        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }

    public function ensureAccount(Expert $expert): string
    {
        if ($expert->stripe_account_id) {
            return $expert->stripe_account_id;
        }

        $platformCountry = (string) config('services.stripe.platform_country', 'US');
        $country = $expert->country ?: $platformCountry;

        // Intentionally no `business_type`: it is not universally supported per
        // country (e.g. AE rejects `individual`), and Express collects it in the
        // hosted onboarding anyway. Hardcoding it broke onboarding for those
        // countries. Let the expert pick their type during onboarding.
        $params = [
            'type' => 'express',
            'country' => $country,
            'email' => $expert->email,
            'capabilities' => ['transfers' => ['requested' => true]],
            'metadata' => ['expert_id' => $expert->id],
        ];

        // Cross-border recipients (country != platform country) must accept the
        // `recipient` service agreement — a receive-only account, which is exactly
        // what our experts are. Same-country recipients must NOT set it (Stripe
        // rejects it), so it is added only when the countries differ.
        if (strtoupper($country) !== strtoupper($platformCountry)) {
            $params['tos_acceptance'] = ['service_agreement' => 'recipient'];
        }

        $account = $this->withoutNoticeWarnings(fn () => $this->stripe->accounts->create($params));

        $expert->update(['stripe_account_id' => $account->id]);

        return $account->id;
    }

    public function onboardingLink(Expert $expert, string $returnUrl, string $refreshUrl): string
    {
        return $this->withoutNoticeWarnings(fn () => $this->stripe->accountLinks->create([
            'account' => $expert->stripe_account_id,
            'return_url' => $returnUrl,
            'refresh_url' => $refreshUrl,
            'type' => 'account_onboarding',
        ]))->url;
    }

    public function refreshStatus(Expert $expert): bool
    {
        $account = $this->withoutNoticeWarnings(fn () => $this->stripe->accounts->retrieve($expert->stripe_account_id));

        $enabled = (bool) ($account->payouts_enabled ?? false);
        $expert->update(['stripe_payouts_enabled' => $enabled]);

        return $enabled;
    }

    public function transfer(Expert $expert, int $amountMinor, string $currency, ?string $description = null, ?string $idempotencyKey = null): Transfer
    {
        return $this->withoutNoticeWarnings(fn () => $this->stripe->transfers->create([
            'amount' => $amountMinor,
            'currency' => strtolower($currency),
            'destination' => $expert->stripe_account_id,
            'description' => $description,
            'metadata' => ['expert_id' => $expert->id],
        ], $idempotencyKey ? ['idempotency_key' => $idempotencyKey] : []));
    }
}
