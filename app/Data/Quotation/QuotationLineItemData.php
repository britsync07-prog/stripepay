<?php

namespace App\Data\Quotation;

use App\Models\ConsultationRequest;
use Spatie\LaravelData\Data;

/**
 * One priced row of a quotation's line-items table. Amounts are pre-formatted
 * for display; the raw minor units live only in the stored `line_items` JSON.
 */
class QuotationLineItemData extends Data
{
    public function __construct(
        public string $description,
        public int $quantity,
        public string $unitPrice,
        public string $total,
    ) {}

    /**
     * @param  array{description: string, quantity: int, unit_amount: int}  $item
     */
    public static function fromArray(array $item, string $currency): self
    {
        $quantity = (int) $item['quantity'];
        $unit = (int) $item['unit_amount'];

        return new self(
            description: (string) $item['description'],
            quantity: $quantity,
            unitPrice: ConsultationRequest::formatMoneyMinorExact($unit, $currency),
            total: ConsultationRequest::formatMoneyMinorExact($unit * $quantity, $currency),
        );
    }
}
