<?php

namespace App\Actions\Admin;

use App\Models\Expert;
use App\Services\Payment\StripeConnect;
use Lorisleiva\Actions\Concerns\AsAction;

/** Stripe's account-link refresh target — re-issues a fresh onboarding link. */
class StripeRefresh
{
    use AsAction;

    public function asController(Expert $expert)
    {
        $connect = app(StripeConnect::class);
        $connect->ensureAccount($expert);

        $url = $connect->onboardingLink(
            $expert,
            returnUrl: route('admin.experts.stripe.return', $expert),
            refreshUrl: route('admin.experts.stripe.refresh', $expert),
        );

        return redirect()->away($url);
    }
}
