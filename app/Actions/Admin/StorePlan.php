<?php

namespace App\Actions\Admin;

use App\Enums\BillingInterval;
use App\Models\Plan;
use App\Services\SettingsService;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class StorePlan
{
    use AsAction;

    public function asController(Request $request)
    {
        $data = $request->validate(self::rules());

        $payload = self::payload($data);
        // New plans go to the end of the list; order is managed by drag-and-drop.
        $payload['sort_order'] = (int) (Plan::max('sort_order') ?? 0) + 1;

        Plan::create($payload);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Plan created.']);

        return redirect()->route('admin.plans');
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(?int $planId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('plans', 'slug')->ignore($planId)],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'billing_interval' => ['required', Rule::enum(BillingInterval::class)],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'outcome' => ['nullable', 'string', 'max:500'],
            'cta_label' => ['required', 'string', 'max:50'],
            'is_featured' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * Map validated form input to model attributes — price comes in as a major
     * unit (dollars) and is stored as integer minor units (brick/money).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function payload(array $data): array
    {
        $data['price'] = Money::of((string) $data['price'], SettingsService::currencyCode(), roundingMode: RoundingMode::HALF_UP)
            ->getMinorAmount()
            ->toInt();
        // System currency is fixed — never taken from input.
        $data['currency'] = SettingsService::currencyCode();
        $data['features'] = array_values(array_filter(
            array_map('trim', $data['features'] ?? []),
            fn (string $f) => $f !== '',
        ));

        return $data;
    }
}
