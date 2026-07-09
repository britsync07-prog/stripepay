<?php

namespace App\Actions\Admin;

use App\Enums\InvoiceStatus;
use App\Models\ConsultationRequest;
use App\Models\Invoice;
use App\Services\SettingsService;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateInvoiceFromConsultation
{
    use AsAction;

    public function handle(ConsultationRequest $consultation): ?Invoice
    {
        if ((int) $consultation->amount <= 0) {
            return null;
        }

        $existing = Invoice::where('consultation_request_id', $consultation->id)->first();

        if ($existing) {
            return $existing;
        }

        $invoice = Invoice::create([
            'user_id' => $consultation->user_id,
            'consultation_request_id' => $consultation->id,
            'currency' => $consultation->currency,
            'status' => InvoiceStatus::Paid,
            'tax_rate' => SettingsService::taxRate(),
            'issued_at' => $consultation->paid_at ?? now(),
            'paid_at' => $consultation->paid_at ?? now(),
        ]);

        $invoice->items()->create([
            'description' => $consultation->offerTitle(),
            'quantity' => 1,
            'unit_amount' => (int) $consultation->amount,
            'amount' => (int) $consultation->amount,
            'sort_order' => 1,
        ]);

        $invoice->recalculateTotals();

        activity('invoice')
            ->performedOn($invoice)
            ->withProperties([
                'type' => 'issued',
                'message' => "Invoice {$invoice->reference} issued",
                'total' => $invoice->total,
                'currency' => $invoice->currency,
            ])
            ->log('Invoice issued');

        return $invoice;
    }
}
