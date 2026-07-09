<?php

namespace App\Data\ConsultationRequest;

use App\Models\ConsultationRequest;
use Spatie\LaravelData\Data;

/**
 * A single row on the admin Payment Links list: the shareable checkout URL, the
 * price, and its paid/expired state. Purpose-built for the payment-links surface
 * (a priced custom request that is not a CRM lead).
 */
class PaymentLinkListData extends Data
{
    public function __construct(
        public string $uuid,
        public string $reference,
        public string $title,
        public string $amount,
        public string $status,
        public string $status_label,
        public bool $is_paid,
        public bool $expired,
        public string $url,
        public string $manage_url,
        public ?string $name,
        public ?string $email,
        public ?string $created_at,
    ) {}

    public static function fromModel(ConsultationRequest $c): self
    {
        return new self(
            uuid: $c->uuid,
            reference: $c->reference ?? strtoupper(substr($c->uuid, 0, 8)),
            title: $c->offerTitle(),
            amount: $c->formattedAmount(),
            status: $c->status->value,
            status_label: $c->status->label(),
            is_paid: $c->isPaid(),
            expired: $c->isExpired(),
            url: $c->checkoutUrl(),
            manage_url: route('admin.consultation.payment-link', $c, false),
            name: $c->name,
            email: $c->email,
            created_at: $c->created_at?->toFormattedDayDateString(),
        );
    }
}
