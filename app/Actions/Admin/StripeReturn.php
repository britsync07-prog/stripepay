<?php

namespace App\Actions\Admin;

use App\Models\Expert;
use App\Services\Payment\StripeConnect;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/** Landing action when Stripe redirects back after onboarding — refreshes status. */
class StripeReturn
{
    use AsAction;

    public function asController(Expert $expert)
    {
        $enabled = app(StripeConnect::class)->refreshStatus($expert);

        Inertia::flash('toast', [
            'type' => $enabled ? 'success' : 'info',
            'message' => $enabled
                ? 'Stripe payouts are now enabled for this expert.'
                : 'Stripe onboarding is incomplete — some details are still pending.',
        ]);

        return redirect()->route('admin.experts.edit', $expert);
    }
}
