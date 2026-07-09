<?php

namespace App\Actions\Admin;

use App\Data\Plan\PlanData;
use App\Models\Plan;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ListPlans
{
    use AsAction;

    public function asController()
    {
        $plans = Plan::orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Plan $plan) => PlanData::fromModel($plan));

        return Inertia::render('admin/plans', [
            'plans' => $plans,
        ]);
    }
}
