<?php

namespace App\Actions\Admin;

use App\Models\QuotationTemplate;
use App\Services\SettingsService;
use App\Support\QuotationLineItems;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Creates a reusable quotation template. Line-item unit prices arrive in major
 * units and are stored as minor units (brick/money) so a template drops onto a
 * quotation with no re-conversion.
 */
class StoreQuotationTemplate
{
    use AsAction;

    public function asController(Request $request)
    {
        $data = $request->validate(self::rules());

        QuotationTemplate::create(self::payload($data));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Template created.']);

        return redirect()->route('admin.quotation-templates');
    }

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'scope_items' => ['nullable', 'array'],
            'scope_items.*' => ['nullable', 'string', 'max:255'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.description' => ['required', 'string', 'max:255'],
            'line_items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'line_items.*.unit_amount' => ['required', 'numeric', 'min:0'],
            'delivery_timeline' => ['nullable', 'string', 'max:255'],
            'valid_days' => ['required', 'integer', 'min:1', 'max:365'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function payload(array $data): array
    {
        $currency = SettingsService::currencyCode();

        return [
            'name' => $data['name'],
            'title' => $data['title'],
            'scope_items' => QuotationLineItems::scope($data['scope_items'] ?? []),
            'line_items' => QuotationLineItems::toMinor($data['line_items'], $currency),
            'delivery_timeline' => ($data['delivery_timeline'] ?? null) ?: null,
            'valid_days' => (int) $data['valid_days'],
            'currency' => $currency,
            'is_active' => (bool) $data['is_active'],
        ];
    }
}
