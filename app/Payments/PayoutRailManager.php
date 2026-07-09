<?php

namespace App\Payments;

use App\Payments\Contracts\PayoutRail;
use App\Payments\Rails\StripeRail;
use Illuminate\Support\Manager;

/**
 * Resolves payout rails by key (Laravel's Manager pattern), mirroring
 * {@see PaymentManager}. Add a rail with a create*Driver() method + a config key
 * in config('services.payouts.rails').
 */
class PayoutRailManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return (string) config('services.payouts.default', 'stripe');
    }

    public function rail(string $key): PayoutRail
    {
        return $this->driver($key);
    }

    /**
     * Configured rails keyed by their key, in config order.
     *
     * @return array<string, PayoutRail>
     */
    public function configured(): array
    {
        $rails = [];

        foreach ((array) config('services.payouts.rails', []) as $key) {
            $rail = $this->driver($key);

            if ($rail->isConfigured()) {
                $rails[$key] = $rail;
            }
        }

        return $rails;
    }

    protected function createStripeDriver(): PayoutRail
    {
        return new StripeRail;
    }
}
