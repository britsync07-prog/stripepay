<?php

namespace App\Payments;

/**
 * A normalized, gateway-agnostic snapshot of a confirmed payment. Every gateway
 * maps its own API response into this shape so the recording logic stays single.
 */
final class VerifiedPayment
{
    public function __construct(
        public readonly string $reference,
        public readonly int $amountMinor,
        public readonly string $currency,
        public readonly ?string $payerName = null,
        public readonly ?string $payerEmail = null,
        public readonly ?string $payerPhone = null,
        public readonly ?string $payerCountry = null,
        public readonly ?int $feeMinor = null,
        public readonly ?int $netMinor = null,
        public readonly array $raw = [],
    ) {}
}
