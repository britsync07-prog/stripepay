<?php

namespace App\Payments;

/** Where a gateway should send the buyer after success / cancellation. */
final class CheckoutUrls
{
    public function __construct(
        public readonly string $returnUrl,
        public readonly string $cancelUrl,
    ) {}
}
