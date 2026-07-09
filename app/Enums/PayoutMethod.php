<?php

namespace App\Enums;

use App\Payments\PayoutRailManager;

/**
 * Money-out rails. The platform pays experts via Stripe Connect (Express).
 * Values match the rail keys resolved by {@see PayoutRailManager}.
 */
enum PayoutMethod: string
{
    case Stripe = 'stripe';

    public function label(): string
    {
        return match ($this) {
            self::Stripe => 'Stripe',
        };
    }

    /**
     * Value/label pairs for frontend select inputs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $m) => ['value' => $m->value, 'label' => $m->label()],
            self::cases(),
        );
    }
}
