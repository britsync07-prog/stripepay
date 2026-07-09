<?php

namespace App\Actions\Admin;

use App\Enums\LeadStage;
use App\Models\ConsultationRequest;
use App\Models\User;
use App\Services\SettingsService;
use App\Support\QuotationLineItems;
use App\Support\QuoteNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Issues (or re-issues) a formal quotation on a custom request: a bulleted scope
 * of services, an itemised line-items table and a validity window. The total is
 * derived from the line items — never entered directly — so the document and the
 * charged amount can never disagree. A document number (BDA-YYYY-NNN) is assigned
 * once and preserved across re-issues; pricing the request advances the lead to
 * Quoted, exactly like {@see SetConsultationPrice}.
 */
class BuildQuotation
{
    use AsAction;

    /**
     * Apply a quotation to a request from already-normalised (minor-unit) line
     * items. Shared by the admin builder and the client-less link creator
     * ({@see CreateQuotationLink}), so both go through the same money + numbering
     * rules. Throws when the derived total is not positive (unpayable).
     *
     * @param  array<int, array{description: string, quantity: int, unit_amount: int}>  $minorItems
     * @param  array<int, string>  $scopeItems
     */
    public static function issue(
        ConsultationRequest $consultationRequest,
        array $minorItems,
        array $scopeItems,
        ?string $title,
        ?string $deliveryTimeline,
        int $validDays,
        ?User $causer = null,
    ): void {
        $totalMinor = QuotationLineItems::total($minorItems);

        // A quotation must be payable — checkout rejects a zero-amount custom
        // request (ShowCheckout), so an all-zero line-items total is invalid.
        if ($totalMinor <= 0) {
            throw ValidationException::withMessages([
                'line_items' => 'The quotation total must be greater than zero.',
            ]);
        }

        $payload = [
            'quote_issued_at' => now()->startOfDay(),
            'title' => ($title ?: null) ?? $consultationRequest->title,
            'scope_items' => $scopeItems,
            'line_items' => $minorItems,
            'delivery_timeline' => $deliveryTimeline,
            'amount' => $totalMinor,
            'currency' => SettingsService::currencyCode(),
            'expires_at' => now()->addDays($validDays),
        ];

        // The document number is assigned once and preserved across re-issues.
        // Allocation is a read-then-write, so tolerate a concurrent issuer losing
        // the unique-index race by recomputing the next number and retrying.
        if ($consultationRequest->quote_number === null) {
            retry(3, function () use ($consultationRequest, $payload) {
                $consultationRequest->update([...$payload, 'quote_number' => QuoteNumber::next()]);
            }, 0);
        } else {
            $consultationRequest->update($payload);
        }

        activity('consultation')
            ->causedBy($causer)
            ->performedOn($consultationRequest)
            ->withProperties([
                'type' => 'quotation',
                'message' => 'Issued quotation '.$consultationRequest->quote_number,
                'amount' => $totalMinor,
                'currency' => $consultationRequest->currency,
            ])
            ->log('Issued quotation');

        // Pricing the request *is* the offer — the lead is now Quoted.
        AutoAdvanceLead::run($consultationRequest, LeadStage::Quoted, $causer);
    }

    public function asController(Request $request, ConsultationRequest $consultationRequest)
    {
        abort_unless($consultationRequest->isCustom(), 404);
        abort_if($consultationRequest->isPaid(), 409, 'This request is already paid.');

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'scope_items' => ['nullable', 'array'],
            // Blank rows are tolerated and dropped below (the builder can post empties).
            'scope_items.*' => ['nullable', 'string', 'max:255'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.description' => ['required', 'string', 'max:255'],
            'line_items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'line_items.*.unit_amount' => ['required', 'numeric', 'min:0'],
            'expires_in_days' => ['required', 'integer', 'min:1', 'max:365'],
            'delivery_timeline' => ['nullable', 'string', 'max:255'],
        ]);

        $currency = SettingsService::currencyCode();

        self::issue(
            consultationRequest: $consultationRequest,
            minorItems: QuotationLineItems::toMinor($data['line_items'], $currency),
            scopeItems: QuotationLineItems::scope($data['scope_items'] ?? []),
            title: $data['title'] ?? null,
            deliveryTimeline: $data['delivery_timeline'] ?? null,
            validDays: (int) $data['expires_in_days'],
            causer: $request->user(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Quotation '.$consultationRequest->quote_number.' ready. Copy the link or email it to the client.',
        ]);

        return $request->boolean('stay')
            ? back()
            : redirect()->route('admin.consultation.payment-link', $consultationRequest);
    }
}
