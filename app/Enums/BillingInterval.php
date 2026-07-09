<?php

namespace App\Enums;

enum BillingInterval: string
{
    case PerEngagement = 'per_engagement';
    case PerMission = 'per_mission';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::PerEngagement => 'per engagement',
            self::PerMission => 'per mission',
            self::Monthly => 'per month',
            self::Yearly => 'per year',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $i) => ['value' => $i->value, 'label' => $i->label()],
            self::cases(),
        );
    }
}
