<?php

namespace App\Actions\Admin;

use App\Enums\PayoutMethod;
use App\Models\Expert;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class StoreExpert
{
    use AsAction;

    public function asController(Request $request)
    {
        $data = $request->validate($this->rules());

        Expert::create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expert added.']);

        return redirect()->route('admin.experts');
    }

    public static function rules(?int $expertId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('experts', 'email')->ignore($expertId)],
            'specialization' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2'],
            'is_active' => ['required', 'boolean'],
            'preferred_payout_method' => ['required', Rule::enum(PayoutMethod::class)],
            'payout_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
