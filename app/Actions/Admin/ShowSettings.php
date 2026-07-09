<?php

namespace App\Actions\Admin;

use App\Models\Currency;
use App\Settings\PlatformSettings;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ShowSettings
{
    use AsAction;

    public function asController(PlatformSettings $settings)
    {
        return Inertia::render('admin/settings', [
            'currencies' => Currency::options(),
            'settings' => [
                'currency_code' => $settings->currency_code,
                'currency_symbol' => $settings->currency_symbol,
                'tax_rate' => $settings->tax_rate,
                'company_name' => $settings->company_name,
                'company_email' => $settings->company_email,
                'company_address' => $settings->company_address,
                'vat_number' => $settings->vat_number,
                'invoice_footer' => $settings->invoice_footer,
                'signatory_name' => $settings->signatory_name,
                'signatory_title' => $settings->signatory_title,
            ],
        ]);
    }
}
