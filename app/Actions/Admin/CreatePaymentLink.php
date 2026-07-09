<?php

namespace App\Actions\Admin;

use App\Enums\ConsultationStatus;
use App\Models\ConsultationRequest;
use App\Services\SettingsService;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Creates a standalone payment link: the admin sets only a price (and an
 * optional label), and gets a shareable checkout URL. No plan, no CRM lead, no
 * account — the buyer fills in their own details at checkout, and the existing
 * RecordConsultationPayment funnel provisions them and issues the receipt on pay.
 */
class CreatePaymentLink
{
    use AsAction;

    public function asController(Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $currency = SettingsService::currencyCode();

        $minor = Money::of((string) $data['amount'], $currency, roundingMode: RoundingMode::HALF_UP)
            ->getMinorAmount()
            ->toInt();

        $link = ConsultationRequest::create([
            'plan_id' => null,
            'title' => ($data['title'] ?? null) ?: 'Consultation',
            'amount' => $minor,
            'currency' => $currency,
            'status' => ConsultationStatus::Pending,
            'expires_at' => isset($data['expires_in_days'])
                ? now()->addDays((int) $data['expires_in_days'])
                : null,
        ]);

        activity('payment')
            ->causedBy($request->user())
            ->performedOn($link)
            ->withProperties([
                'type' => 'payment_link',
                'amount' => $minor,
                'currency' => $currency,
                'message' => 'Created payment link',
            ])
            ->log('Created payment link');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Payment link created — copy it and share with your client.',
        ]);

        return redirect()->route('admin.consultation.payment-link', $link);
    }
}
