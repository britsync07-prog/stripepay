<?php

namespace App\Actions\Admin;

use App\Data\Expert\ExpertData;
use App\Enums\PayoutMethod;
use App\Models\Expert;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ShowExpertForm
{
    use AsAction;

    public function asController(?Expert $expert = null)
    {
        return Inertia::render('admin/experts/form', [
            'expert' => $expert?->exists ? ExpertData::fromModel($expert) : null,
            'payoutMethods' => PayoutMethod::options(),
        ]);
    }
}
