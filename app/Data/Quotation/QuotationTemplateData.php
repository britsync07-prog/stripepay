<?php

namespace App\Data\Quotation;

use App\Models\QuotationTemplate;
use Brick\Money\Money;
use Spatie\LaravelData\Data;

/**
 * Admin-facing view model for a quotation template — drives the list, the edit
 * form and the "start from a template" pickers. Line-item amounts are exposed in
 * major units (dollars) for editing; the model stores them as minor units.
 */
class QuotationTemplateData extends Data
{
    public function __construct(
        public string $uuid,
        public string $name,
        public string $title,
        /** @var array<int, string> */
        public array $scopeItems,
        /** @var array<int, array{description: string, quantity: int, unit_amount: string}> */
        public array $lineItems,
        public ?string $deliveryTimeline,
        public int $validDays,
        public bool $isActive,
        public string $total,
        public int $lineItemCount,
    ) {}

    public static function fromModel(QuotationTemplate $template): self
    {
        $currency = (string) $template->currency;

        $lineItems = array_map(fn (array $item) => [
            'description' => (string) $item['description'],
            'quantity' => (int) $item['quantity'],
            'unit_amount' => (string) Money::ofMinor((int) $item['unit_amount'], $currency)->getAmount(),
        ], $template->line_items ?? []);

        return new self(
            uuid: $template->uuid,
            name: $template->name,
            title: $template->title,
            scopeItems: array_values($template->scope_items ?? []),
            lineItems: $lineItems,
            deliveryTimeline: $template->delivery_timeline,
            validDays: $template->valid_days,
            isActive: $template->is_active,
            total: $template->formattedTotal(),
            lineItemCount: count($template->line_items ?? []),
        );
    }
}
