<?php

namespace App\Actions\Admin;

use App\Models\Expert;
use App\Payments\PayoutRailManager;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Starts payout onboarding for an expert: ensures a recipient on the default
 * rail (Stripe Connect) and redirects to its hosted onboarding, where the expert
 * enters their bank/card + identity details. Completion is reflected on return
 * (or via the "Refresh status" action).
 */
class OnboardExpert
{
    use AsAction;

    public function asController(Expert $expert, PayoutRailManager $rails)
    {
        $rail = $rails->rail($rails->getDefaultDriver());

        try {
            $url = $rail->onboardingUrl($expert);
        } catch (\Throwable $e) {
            // Record the real failure for diagnosis, then surface a toast — the
            // button posts via router.post, so a thrown exception would be invisible.
            report($e);

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Could not start '.$rail->label().' onboarding: '.$e->getMessage(),
            ]);

            return back();
        }

        return Inertia::location($url);
    }
}
