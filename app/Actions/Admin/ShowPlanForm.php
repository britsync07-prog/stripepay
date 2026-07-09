<?php

namespace App\Actions\Admin;

use App\Data\Plan\PlanData;
use App\Enums\BillingInterval;
use App\Models\Plan;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ShowPlanForm
{
    use AsAction;

    public function asController(?Plan $plan = null)
    {
        return Inertia::render('admin/plans/form', [
            'plan' => $plan?->exists ? PlanData::fromModel($plan) : null,
            'billingIntervals' => BillingInterval::options(),
        ]);
    }
}
