<?php

namespace App\Data\Expert;

use App\Models\Expert;
use Spatie\LaravelData\Data;

class ExpertData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $email,
        public ?string $specialization,
        public ?string $country,
        public bool $is_active,
        public string $preferred_payout_method,
        public ?string $payout_notes,
        public ?string $stripe_account_id,
        public bool $stripe_payouts_enabled,
    ) {}

    public static function fromModel(Expert $e): self
    {
        return new self(
            id: $e->id,
            name: $e->name,
            email: $e->email,
            specialization: $e->specialization,
            country: $e->country,
            is_active: (bool) $e->is_active,
            preferred_payout_method: $e->preferred_payout_method,
            payout_notes: $e->payout_notes,
            stripe_account_id: $e->stripe_account_id,
            stripe_payouts_enabled: (bool) $e->stripe_payouts_enabled,
        );
    }
}
