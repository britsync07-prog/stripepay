<?php

namespace App\Enums;

enum PayoutStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Paid = 'paid';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Paid => 'Paid',
            self::Failed => 'Failed',
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

    /**
     * Map a provider's payout/payment status to our status. Single source of
     * truth used by the rails (e.g. Stripe transfer → "paid"). Returns null for
     * unknown statuses.
     */
    public static function fromProvider(?string $status): ?self
    {
        return match ($status) {
            'processed', 'complete', 'completed', 'paid' => self::Paid,
            'failed', 'returned', 'cancelled', 'canceled', 'denied' => self::Failed,
            'processing', 'pending', 'submitted' => self::Processing,
            default => null,
        };
    }
}
