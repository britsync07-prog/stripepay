<?php

namespace App\Actions\Admin;

use App\Models\Expert;
use App\Payments\PayoutRailManager;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Re-checks an expert's payout onboarding status with the rail (Stripe) and
 * stores the latest recipient status. Lets an admin confirm onboarding completed
 * without waiting for the webhook.
 */
class RefreshPayoutStatus
{
    use AsAction;

    public function asController(Expert $expert, PayoutRailManager $rails)
    {
        $rail = $rails->rail($rails->getDefaultDriver());

        try {
            $ready = $rail->recipientReady($expert);
        } catch (\Throwable $e) {
            report($e);

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Could not refresh '.$rail->label().' status: '.$e->getMessage(),
            ]);

            return redirect()->route('admin.experts.edit', $expert);
        }

        Inertia::flash('toast', [
            'type' => $ready ? 'success' : 'info',
            'message' => $ready
                ? $rail->label().' payouts are now enabled for this expert.'
                : $rail->label().' onboarding is incomplete — some details are still pending.',
        ]);

        return redirect()->route('admin.experts.edit', $expert);
    }
}
