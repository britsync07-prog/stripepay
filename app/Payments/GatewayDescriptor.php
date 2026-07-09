<?php

namespace App\Payments;

use Spatie\LaravelData\Data;

/** What the checkout frontend needs to render and begin a gateway option. */
class GatewayDescriptor extends Data
{
    public function __construct(
        public string $key,
        public string $label,
        public string $title,                  // buyer-facing CTA, e.g. 'Pay by card'
        public string $mode,                   // 'embedded' (Stripe) | 'buttons' (PayPal SDK)
        public string $sessionUrl,             // lazily begins the checkout when the buyer picks this rail
        public ?string $publishableKey = null, // embedded: public Stripe key
        public ?string $clientId = null,       // buttons: public SDK client id
        public ?string $currency = null,       // buttons
        public ?string $captureUrl = null,     // buttons: capture endpoint
    ) {}
}
