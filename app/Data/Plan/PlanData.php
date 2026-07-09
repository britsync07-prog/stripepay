<?php

namespace App\Data\Plan;

use App\Enums\BillingInterval;
use App\Models\Plan;
use App\Services\SettingsService;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Spatie\LaravelData\Data;

class PlanData extends Data
{
    public function __construct(
        public ?int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public int $price,
        public string $currency,
        public string $formatted_price,
        public BillingInterval $billing_interval,
        public string $interval_label,
        public array $features,
        public ?string $outcome,
        public string $cta_label,
        public bool $is_featured,
        public bool $is_active,
        public int $sort_order,
    ) {}

    public static function fromModel(Plan $plan): self
    {
        return new self(
            id: $plan->id,
            name: $plan->name,
            slug: $plan->slug,
            description: $plan->description,
            price: $plan->price,
            currency: $plan->currency,
            formatted_price: self::formatPrice($plan->price, $plan->currency),
            billing_interval: $plan->billing_interval,
            interval_label: $plan->billing_interval->label(),
            features: $plan->features ?? [],
            outcome: $plan->outcome,
            cta_label: $plan->cta_label,
            is_featured: $plan->is_featured,
            is_active: $plan->is_active,
            sort_order: $plan->sort_order,
        );
    }

    private static function formatPrice(int $minor, string $currency): string
    {
        $money = Money::ofMinor($minor, $currency);
        $whole = $money->getAmount()->toScale(0, RoundingMode::DOWN);
        $symbol = $currency === SettingsService::currencyCode() ? SettingsService::currencySymbol() : $currency.' ';

        return $symbol.number_format((float) (string) $whole);
    }
}
