<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Paid = 'paid';
    case Refunded = 'refunded';
    case Failed = 'failed';
    case Disputed = 'disputed';

    public function label(): string
    {
        return match ($this) {
            self::Paid => 'Paid',
            self::Refunded => 'Refunded',
            self::Failed => 'Failed',
            self::Disputed => 'Disputed',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $s) => ['value' => $s->value, 'label' => $s->label()],
            self::cases(),
        );
    }
}
