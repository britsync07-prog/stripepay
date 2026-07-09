<?php

namespace App\Data\Payment;

use App\Models\Payment;
use Spatie\LaravelData\Data;

class PaymentData extends Data
{
    public function __construct(
        public string $gateway,
        public string $amount,
        public string $status,
        public ?string $plan,
        public ?string $reference,
        public ?string $consultation,
        public ?string $date,
    ) {}

    public static function fromModel(Payment $payment): self
    {
        // Prefer the gateway's transaction id (capture id / payment intent) — the id
        // that's searchable in the provider dashboard — falling back to the internal
        // reference (order / session id) used as the idempotency key.
        $transactionId = $payment->payload['transaction_id'] ?? $payment->reference;

        return new self(
            gateway: $payment->gateway,
            amount: $payment->formattedAmount(),
            status: $payment->status->value,
            plan: $payment->consultationRequest?->plan?->name ?? 'Consultation',
            reference: $transactionId,
            consultation: $payment->consultationRequest?->uuid,
            date: $payment->paid_at?->translatedFormat('M j, Y · g:i A'),
        );
    }
}
