<?php

namespace App\Actions\Admin;

use App\Models\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdatePlan
{
    use AsAction;

    public function asController(Request $request, Plan $plan)
    {
        $data = $request->validate(StorePlan::rules($plan->id));

        $plan->update(StorePlan::payload($data));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Plan updated.']);

        return redirect()->route('admin.plans');
    }
}
