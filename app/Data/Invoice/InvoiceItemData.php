<?php

namespace App\Data\Invoice;

use App\Models\InvoiceItem;
use Spatie\LaravelData\Data;

class InvoiceItemData extends Data
{
    public function __construct(
        public int $id,
        public string $description,
        public int $quantity,
        public string $unit_amount,
        public string $amount,
    ) {}

    public static function fromModel(InvoiceItem $i, string $currency): self
    {
        return new self(
            id: $i->id,
            description: $i->description,
            quantity: $i->quantity,
            unit_amount: InvoiceItem::formatMoneyMinorExact($i->unit_amount, $currency),
            amount: InvoiceItem::formatMoneyMinorExact($i->amount, $currency),
        );
    }
}
