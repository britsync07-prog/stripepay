<?php

namespace App\Payments;

/** A normalized, rail-agnostic snapshot of a sent payout. */
final class PayoutResult
{
    public function __construct(
        public readonly string $reference,   // provider batch/payment id
        public readonly string $status,      // pending | processed | returned | failed
        public readonly array $raw = [],
    ) {}
}
