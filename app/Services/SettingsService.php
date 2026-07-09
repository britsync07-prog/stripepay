<?php

namespace App\Services;

use App\Settings\PlatformSettings;

final class SettingsService
{
    public const DEFAULT_CURRENCY_CODE = 'USD';

    public const DEFAULT_CURRENCY_SYMBOL = '$';

    public static function currencyCode(): string
    {
        return self::get('currency_code', self::DEFAULT_CURRENCY_CODE);
    }

    public static function currencySymbol(): string
    {
        return self::get('currency_symbol', self::DEFAULT_CURRENCY_SYMBOL);
    }

    public static function taxRate(): float
    {
        return (float) self::get('tax_rate', 0.0);
    }

    public static function companyName(): string
    {
        return self::get('company_name', config('app.name'));
    }

    /**
     * @return array{name: string, email: ?string, address: ?string, vat: ?string, footer: ?string, signatory_name: ?string, signatory_title: ?string}
     */
    public static function company(): array
    {
        return [
            'name' => self::companyName(),
            'email' => self::get('company_email', null),
            'address' => self::get('company_address', null),
            'vat' => self::get('vat_number', null),
            'footer' => self::get('invoice_footer', null),
            'signatory_name' => self::get('signatory_name', null),
            'signatory_title' => self::get('signatory_title', null),
        ];
    }

    private static function get(string $key, mixed $default): mixed
    {
        try {
            $value = app(PlatformSettings::class)->{$key};

            return ($value === null || $value === '') ? $default : $value;
        } catch (\Throwable) {
            return $default;
        }
    }
}
