<?php

namespace App\Actions\Admin;

use App\Models\ConsultationRequest;
use App\Notifications\PaymentLinkNotification;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Emails the client the payment link for a consultation request. Sends to a
 * linked client account (mail + in-app notification) or routes the mail to the
 * buyer's email when they have not been provisioned yet.
 */
class SendPaymentLink
{
    use AsAction;

    public function asController(ConsultationRequest $consultationRequest)
    {
        if ($consultationRequest->isPaid()) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'This request is already paid.']);

            return back();
        }

        // An unpriced custom lead has no payable checkout (see ShowCheckout) — its
        // link would 404 for the client. Force the admin to price it first.
        if ($consultationRequest->isCustom() && (int) $consultationRequest->amount <= 0) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Set a price for this custom request before sending the payment link.',
            ]);

            return back();
        }

        if ($consultationRequest->isExpired()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'This payment link has expired — create a new custom request.',
            ]);

            return back();
        }

        $notification = new PaymentLinkNotification($consultationRequest);

        if ($consultationRequest->user) {
            $consultationRequest->user->notify($notification);
        } else {
            Notification::route('mail', $consultationRequest->email)->notify($notification);
        }

        activity('consultation')
            ->causedBy(request()->user())
            ->performedOn($consultationRequest)
            ->withProperties(['email' => $consultationRequest->email])
            ->log('Emailed payment link to client');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Payment link emailed to '.$consultationRequest->email.'.',
        ]);

        return back();
    }
}
