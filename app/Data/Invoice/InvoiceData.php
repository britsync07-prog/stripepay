<?php

namespace App\Data\Invoice;

use App\Models\Invoice;
use Spatie\LaravelData\Data;

class InvoiceData extends Data
{
    public function __construct(
        public string $uuid,
        public string $reference,
        public ?string $client,
        public ?string $consultation_reference,
        public string $status,
        public string $status_label,
        public string $status_tone,
        public string $subtotal,
        public string $tax_rate,
        public string $tax_amount,
        public string $total,
        public string $currency,
        public ?string $notes,
        public ?string $issued_at,
        public ?string $paid_at,
        public ?string $created_at,
        /** @var array<int, InvoiceItemData> */
        public array $items,
    ) {}

    public static function fromModel(Invoice $i): self
    {
        return new self(
            uuid: $i->uuid,
            reference: $i->reference ?? strtoupper(substr($i->uuid, 0, 8)),
            client: $i->client?->name,
            consultation_reference: $i->consultationRequest?->reference,
            status: $i->status->value,
            status_label: $i->status->label(),
            status_tone: $i->status->badge(),
            subtotal: Invoice::formatMoneyMinorExact((int) $i->subtotal, $i->currency),
            tax_rate: rtrim(rtrim((string) $i->tax_rate, '0'), '.'),
            tax_amount: Invoice::formatMoneyMinorExact((int) $i->tax_amount, $i->currency),
            total: Invoice::formatMoneyMinorExact((int) $i->total, $i->currency),
            currency: $i->currency,
            notes: $i->notes,
            issued_at: $i->issued_at?->toFormattedDayDateString(),
            paid_at: $i->paid_at?->toFormattedDayDateString(),
            created_at: $i->created_at?->toFormattedDayDateString(),
            items: $i->items->map(fn ($item) => InvoiceItemData::fromModel($item, $i->currency))->all(),
        );
    }
}
