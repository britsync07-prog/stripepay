<?php

namespace App\Actions\Admin;

use App\Actions\Concerns\RefreshesPayoutStatus;
use App\Enums\PayoutMethod;
use App\Models\Expert;
use App\Payments\PayoutRailManager;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ShowPayoutForm
{
    use AsAction;
    use RefreshesPayoutStatus;

    public function asController(PayoutRailManager $rails)
    {
        $experts = Expert::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'stripe_account_id', 'stripe_payouts_enabled']);

        $this->refreshPendingPayoutStatus($experts, $rails);

        return Inertia::render('admin/payouts/create', [
            'experts' => $experts,
            'payoutMethods' => PayoutMethod::options(),
            'defaultMethod' => $rails->getDefaultDriver(),
        ]);
    }
}
