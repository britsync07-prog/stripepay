<?php

namespace App\Payments;

/**
 * The result of beginning a checkout with a gateway: a stable reference (session
 * or order id) and, for embedded gateways, the client secret to render inline.
 */
final class CheckoutSession
{
    public function __construct(
        public readonly string $mode,            // 'embedded' | 'buttons'
        public readonly ?string $reference = null,
        public readonly ?string $clientSecret = null,
    ) {}
}
