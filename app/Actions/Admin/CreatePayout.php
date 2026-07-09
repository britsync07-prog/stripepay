<?php

namespace App\Actions\Admin;

use App\Enums\PayoutStatus;
use App\Models\Expert;
use App\Models\Payout;
use App\Notifications\PayoutNotification;
use App\Payments\PayoutRailManager;
use App\Services\Media\MediaUploader;
use App\Services\SettingsService;
use App\Support\Uploads;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class CreatePayout
{
    use AsAction;

    public function asController(Request $request, PayoutRailManager $rails)
    {
        $railKeys = array_keys($rails->configured());

        $data = $request->validate([
            'expert_id' => ['required', 'exists:experts,id'],
            'amount' => ['required', 'numeric', 'min:0.5'],
            'method' => ['nullable', Rule::in($railKeys)],
            'note' => ['nullable', 'string', 'max:5000'],
            'proof' => ['nullable', ...Uploads::proofRules()],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ]);

        $rail = $rails->rail($data['method'] ?? $rails->getDefaultDriver());
        $idempotencyKey = $data['idempotency_key'] ?? (string) Str::uuid();

        $existing = Payout::where('idempotency_key', $idempotencyKey)->first();

        if ($existing && in_array($existing->status, [PayoutStatus::Paid, PayoutStatus::Processing], true)) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'This payout was already sent.']);

            return redirect()->route('admin.payouts');
        }

        $currency = SettingsService::currencyCode();

        $minor = Money::of((string) $data['amount'], $currency, roundingMode: RoundingMode::HALF_UP)
            ->getMinorAmount()
            ->toInt();

        $expert = Expert::findOrFail($data['expert_id']);

        if (! $rail->recipientReady($expert)) {
            throw ValidationException::withMessages([
                'expert_id' => 'This expert has not completed '.$rail->label().' payout onboarding yet. Send them the onboarding link first.',
            ]);
        }

        $attributes = [
            'expert_id' => $expert->id,
            'amount' => $minor,
            'currency' => $currency,
            'method' => $rail->key(),
            'reference' => null, // system-owned: set to the rail's batch id on success
            'note' => $data['note'] ?? null,
            'status' => PayoutStatus::Pending,
        ];

        if ($existing) {
            $existing->update($attributes);
            $payout = $existing;
        } else {
            try {
                $payout = Payout::create($attributes + ['idempotency_key' => $idempotencyKey]);
            } catch (UniqueConstraintViolationException $e) {
                Inertia::flash('toast', ['type' => 'info', 'message' => 'This payout was already sent.']);

                return redirect()->route('admin.payouts');
            }
        }

        try {
            $result = $rail->pay($expert, $minor, $currency, 'payout_'.$idempotencyKey, 'Consultation payout');

            // Stripe transfers settle synchronously (→ paid); batch rails would
            // return a pending status (→ processing). The rail tells us which.
            $status = PayoutStatus::fromProvider($result->status) ?? PayoutStatus::Processing;

            $payout->update([
                'reference' => $result->reference,
                'status' => $status,
                'paid_at' => $status === PayoutStatus::Paid ? now() : $payout->paid_at,
            ]);
        } catch (\Throwable $e) {
            report($e);
            $payout->update(['status' => PayoutStatus::Failed]);

            throw ValidationException::withMessages([
                'amount' => $rail->label().' payout failed: '.$e->getMessage(),
            ]);
        }

        if ($request->hasFile('proof')) {
            MediaUploader::make()->store($payout, $request->file('proof'), 'proof');
        }

        activity('payout')
            ->performedOn($payout)
            ->withProperties([
                'expert_id' => $payout->expert_id,
                'amount' => $payout->amount,
                'currency' => $payout->currency,
                'method' => $payout->method->value,
                'reference' => $payout->reference,
            ])
            ->log('Payout sent');

        if ($expert->email) {
            Notification::route('mail', $expert->email)->notify(new PayoutNotification($payout));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payout sent.']);

        return redirect()->route('admin.payouts');
    }
}
