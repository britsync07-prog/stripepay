<?php

namespace App\Support;

use Brick\Math\RoundingMode;
use Brick\Money\Money;

/**
 * Shared quotation line-item + scope helpers, so the builder, the client-less
 * link creator and the template CRUD all normalise money the same way — integer
 * minor units via brick/money, never floats.
 */
final class QuotationLineItems
{
    /**
     * Convert submitted line items (unit_amount in MAJOR units) into stored form
     * (unit_amount in MINOR units).
     *
     * @param  array<int, array{description: string, quantity: int|string, unit_amount: int|string}>  $lineItems
     * @return array<int, array{description: string, quantity: int, unit_amount: int}>
     */
    public static function toMinor(array $lineItems, string $currency): array
    {
        return array_map(fn (array $item) => [
            'description' => (string) $item['description'],
            'quantity' => (int) $item['quantity'],
            'unit_amount' => Money::of((string) $item['unit_amount'], $currency, roundingMode: RoundingMode::HALF_UP)
                ->getMinorAmount()
                ->toInt(),
        ], $lineItems);
    }

    /**
     * Total in minor units for line items whose unit_amount is already minor.
     *
     * @param  array<int, array{quantity: int, unit_amount: int}>  $minorItems
     */
    public static function total(array $minorItems): int
    {
        return array_reduce(
            $minorItems,
            fn (int $carry, array $item) => $carry + ((int) $item['unit_amount'] * (int) $item['quantity']),
            0,
        );
    }

    /**
     * Trim + drop blank scope rows.
     *
     * @param  array<int, string|null>  $scope
     * @return array<int, string>
     */
    public static function scope(array $scope): array
    {
        return array_values(array_filter(
            array_map(fn ($item) => trim((string) $item), $scope),
            fn (string $item) => $item !== '',
        ));
    }
}
