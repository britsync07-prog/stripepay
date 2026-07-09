<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PlatformSettings extends Settings
{
    public string $currency_code;

    public string $currency_symbol;

    public float $tax_rate;

    public string $company_name;

    public ?string $company_email;

    public ?string $company_address;

    public ?string $vat_number;

    public ?string $invoice_footer;

    public ?string $signatory_name;

    public ?string $signatory_title;

    public static function group(): string
    {
        return 'platform';
    }
}
