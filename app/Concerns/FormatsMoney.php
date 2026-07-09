<?php

namespace App\Concerns;

use App\Services\SettingsService;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

trait FormatsMoney
{
    public function formattedAmount(): string
    {
        return self::formatMoneyMinor((int) $this->amount, (string) $this->currency);
    }

    public static function formatMoneyMinor(int $minor, string $currency): string
    {
        $whole = Money::ofMinor($minor, $currency)->getAmount()->toScale(0, RoundingMode::DOWN);
        $symbol = $currency === SettingsService::currencyCode() ? SettingsService::currencySymbol() : $currency.' ';

        return $symbol.number_format((float) (string) $whole);
    }

    public static function formatMoneyMinorExact(int $minor, string $currency): string
    {
        $amount = Money::ofMinor($minor, $currency)->getAmount();
        $symbol = $currency === SettingsService::currencyCode() ? SettingsService::currencySymbol() : $currency.' ';

        return $symbol.number_format((float) (string) $amount, 2);
    }
}
