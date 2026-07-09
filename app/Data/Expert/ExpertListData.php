<?php

namespace App\Data\Expert;

use App\Models\Expert;
use Spatie\LaravelData\Data;

/**
 * Slim projection for the experts index. Only what the list table needs — the
 * raw Stripe account id and payout notes are deliberately omitted so internal
 * identifiers aren't shipped to the browser on a page that never displays them.
 */
class ExpertListData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $email,
        public ?string $specialization,
        public bool $is_active,
        public bool $has_payout_account,
        public bool $stripe_payouts_enabled,
    ) {}

    public static function fromModel(Expert $e): self
    {
        return new self(
            id: $e->id,
            name: $e->name,
            email: $e->email,
            specialization: $e->specialization,
            is_active: (bool) $e->is_active,
            has_payout_account: filled($e->stripe_account_id),
            stripe_payouts_enabled: (bool) $e->stripe_payouts_enabled,
        );
    }
}
