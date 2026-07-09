<?php

namespace App\Data\Payout;

use App\Models\Payout;
use Spatie\LaravelData\Data;

class PayoutData extends Data
{
    public function __construct(
        public int $id,
        public ?string $expert,
        public string $amount,
        public string $currency,
        public string $method,
        public string $method_label,
        public string $status,
        public string $status_label,
        public ?string $reference,
        public ?string $note,
        public ?string $paid_at,
        public ?string $created_at,
        public ?string $proof_url,
    ) {}

    public static function fromModel(Payout $p): self
    {
        $proof = $p->getFirstMedia('proof');

        return new self(
            id: $p->id,
            expert: $p->expert?->name,
            amount: $p->formattedAmount(),
            currency: $p->currency,
            method: $p->method->value,
            method_label: $p->method->label(),
            status: $p->status->value,
            status_label: $p->status->label(),
            reference: $p->reference,
            note: $p->note,
            paid_at: $p->paid_at?->toFormattedDayDateString(),
            created_at: $p->created_at?->toFormattedDayDateString(),
            proof_url: $proof ? route('admin.payouts.proof.show', [$p->id, $proof->id]) : null,
        );
    }
}
