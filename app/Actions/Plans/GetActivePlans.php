<?php

namespace App\Actions\Plans;

use App\Data\Plan\PlanData;
use App\Models\Plan;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class GetActivePlans
{
    use AsAction;

    public function handle(): Collection
    {
        return Plan::active()->get()->map(fn (Plan $plan) => PlanData::fromModel($plan));
    }
}
