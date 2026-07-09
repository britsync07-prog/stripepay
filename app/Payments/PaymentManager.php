<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Gateways\PaypalGateway;
use App\Payments\Gateways\StripeGateway;
use Illuminate\Support\Manager;

class PaymentManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return (string) config('services.payments.default', 'stripe');
    }

    public function gateway(string $key): PaymentGateway
    {
        return $this->driver($key);
    }

    /**
     * Configured gateways, keyed by their machine key, in config order.
     *
     * @return array<string, PaymentGateway>
     */
    public function configured(): array
    {
        $gateways = [];

        foreach ((array) config('services.payments.gateways', []) as $key) {
            $gateway = $this->driver($key);

            if ($gateway->isConfigured()) {
                $gateways[$key] = $gateway;
            }
        }

        return $gateways;
    }

    /**
     * Registered gateway keys as a self-map (key => key) for the frontend.
     *
     * @return array<string, string>
     */
    public function keys(): array
    {
        $keys = [];

        foreach ((array) config('services.payments.gateways', []) as $key) {
            $keys[$key] = $key;
        }

        return $keys;
    }

    /**
     * Render modes as a self-map (mode => mode) for the frontend.
     *
     * @return array<string, string>
     */
    public function modes(): array
    {
        return [
            PaymentGateway::MODE_EMBEDDED => PaymentGateway::MODE_EMBEDDED,
            PaymentGateway::MODE_BUTTONS => PaymentGateway::MODE_BUTTONS,
        ];
    }

    protected function createStripeDriver(): PaymentGateway
    {
        return new StripeGateway;
    }

    protected function createPaypalDriver(): PaymentGateway
    {
        return new PaypalGateway;
    }
}
