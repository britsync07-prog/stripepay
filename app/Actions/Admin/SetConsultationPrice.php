<?php

namespace App\Actions\Admin;

use App\Enums\LeadStage;
use App\Models\ConsultationRequest;
use App\Services\SettingsService;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Prices a custom lead (a client-requested tailored plan). Sets the amount,
 * currency, an optional refined title and a pay-by date — turning the lead into
 * a payable request whose checkout link can then be sent.
 */
class SetConsultationPrice
{
    use AsAction;

    public function asController(Request $request, ConsultationRequest $consultationRequest)
    {
        abort_unless($consultationRequest->isCustom(), 404);
        abort_if($consultationRequest->isPaid(), 409, 'This request is already paid.');

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
            'expires_in_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $currency = SettingsService::currencyCode();

        $minor = Money::of((string) $data['amount'], $currency, roundingMode: RoundingMode::HALF_UP)
            ->getMinorAmount()
            ->toInt();

        $consultationRequest->update([
            'title' => ($data['title'] ?? null) ?: $consultationRequest->title,
            'amount' => $minor,
            'currency' => $currency,
            'expires_at' => now()->addDays((int) $data['expires_in_days']),
            // A flat price supersedes any prior formal quotation. Drop the stale
            // document (number, dates, scope, line items) so it can never be shown
            // with a total that disagrees with the amount actually charged.
            'quote_number' => null,
            'quote_issued_at' => null,
            'scope_items' => null,
            'line_items' => null,
            'delivery_timeline' => null,
        ]);

        activity('consultation')
            ->causedBy($request->user())
            ->performedOn($consultationRequest)
            ->withProperties(['amount' => $minor, 'currency' => $currency])
            ->log('Priced custom request');

        // Pricing the request *is* the offer — the lead is now Quoted.
        AutoAdvanceLead::run($consultationRequest, LeadStage::Quoted, $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Price set. Copy the payment link or email it to the client.',
        ]);

        // The in-place "manage" dialog asks to stay on the consultation; the
        // first-time pricing flow lands on the dedicated payment-link page.
        return $request->boolean('stay')
            ? back()
            : redirect()->route('admin.consultation.payment-link', $consultationRequest);
    }
}
