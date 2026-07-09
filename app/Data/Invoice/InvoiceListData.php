<?php

namespace App\Data\Invoice;

use App\Models\Invoice;
use Spatie\LaravelData\Data;

class InvoiceListData extends Data
{
    public function __construct(
        public string $uuid,
        public string $reference,
        public ?string $client,
        public string $status,
        public string $status_label,
        public string $status_tone,
        public string $total,
        public ?string $issued_at,
        public ?string $created_at,
    ) {}

    public static function fromModel(Invoice $i): self
    {
        return new self(
            uuid: $i->uuid,
            reference: $i->reference ?? strtoupper(substr($i->uuid, 0, 8)),
            client: $i->client?->name,
            status: $i->status->value,
            status_label: $i->status->label(),
            status_tone: $i->status->badge(),
            total: Invoice::formatMoneyMinorExact((int) $i->total, $i->currency),
            issued_at: $i->issued_at?->toFormattedDayDateString(),
            created_at: $i->created_at?->toFormattedDayDateString(),
        );
    }
}
