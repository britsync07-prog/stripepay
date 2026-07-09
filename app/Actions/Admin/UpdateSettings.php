<?php

namespace App\Actions\Admin;

use App\Models\Currency;
use App\Settings\PlatformSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateSettings
{
    use AsAction;

    public function asController(Request $request, PlatformSettings $settings)
    {
        $data = $request->validate([
            'currency_code' => ['required', 'string', Rule::exists('currencies', 'code')],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'company_name' => ['required', 'string', 'max:255'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:1000'],
            'vat_number' => ['nullable', 'string', 'max:100'],
            'invoice_footer' => ['nullable', 'string', 'max:1000'],
            'signatory_name' => ['nullable', 'string', 'max:255'],
            'signatory_title' => ['nullable', 'string', 'max:255'],
        ]);

        $currency = Currency::where('code', $data['currency_code'])->firstOrFail();

        $settings->currency_code = $currency->code;
        $settings->currency_symbol = $currency->symbol;
        $settings->tax_rate = (float) $data['tax_rate'];
        $settings->company_name = $data['company_name'];
        $settings->company_email = ($data['company_email'] ?? null) ?: null;
        $settings->company_address = ($data['company_address'] ?? null) ?: null;
        $settings->vat_number = ($data['vat_number'] ?? null) ?: null;
        $settings->invoice_footer = ($data['invoice_footer'] ?? null) ?: null;
        $settings->signatory_name = ($data['signatory_name'] ?? null) ?: null;
        $settings->signatory_title = ($data['signatory_title'] ?? null) ?: null;
        $settings->save();

        activity('settings')
            ->causedBy(Auth::user())
            ->withProperties(['type' => 'updated', 'message' => 'Platform settings updated'])
            ->log('Platform settings updated');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Settings saved.',
        ]);

        return back();
    }
}
