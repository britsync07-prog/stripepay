<?php

namespace App\Actions\Admin;

use App\Models\Plan;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class DeletePlan
{
    use AsAction;

    public function asController(Plan $plan)
    {
        // consultation_requests.plan_id is nullOnDelete and the paid amount is
        // snapshotted on the request, so removing a plan is safe for history.
        $plan->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Plan deleted.']);

        return redirect()->route('admin.plans');
    }
}
