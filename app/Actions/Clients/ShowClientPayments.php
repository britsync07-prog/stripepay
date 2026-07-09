<?php

namespace App\Actions\Clients;

use App\Data\Payment\PaymentData;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ShowClientPayments
{
    use AsAction;

    public function asController(Request $request)
    {
        $payments = Payment::whereHas(
            'consultationRequest',
            fn ($q) => $q->where('user_id', $request->user()->id)
        )
            ->with('consultationRequest.plan')
            ->latest()
            ->get()
            ->map(fn (Payment $p) => PaymentData::fromModel($p));

        return Inertia::render('portal/payments', [
            'payments' => $payments,
        ]);
    }
}
