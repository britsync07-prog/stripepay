<?php

namespace App\Actions\Clients;

use App\Models\ConsultationRequest;
use App\Models\Contact;
use App\Models\User;
use App\Notifications\DashboardAccessNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class ProvisionClientFromConsultation
{
    use AsAction;

    public function handle(ConsultationRequest $consultationRequest): ?User
    {
        $email = $consultationRequest->email;

        if (! $email) {
            return null;
        }

        $existing = User::where('email', $email)->first();

        if ($existing) {
            // A completed payment confirms ownership of the email — mark verified.
            if (! $existing->hasVerifiedEmail()) {
                $existing->forceFill(['email_verified_at' => now()])->save();
            }

            // Paying makes them a client — a self-registered user (or any prior
            // account) may not carry the role yet, so ensure it here too.
            if (! $existing->hasRole('client')) {
                $existing->assignRole('client');
            }

            $consultationRequest->update(['user_id' => $existing->id]);
            $this->linkContact($consultationRequest, $existing);

            $existing->notify(new DashboardAccessNotification);

            return $existing;
        }

        $user = User::create([
            'name' => $consultationRequest->name ?: 'Client',
            'email' => $email,
            'password' => Hash::make(Str::random(40)),
        ]);

        // email_verified_at isn't mass-assignable — set it explicitly.
        $user->forceFill(['email_verified_at' => now()])->save();

        $user->assignRole('client');

        $consultationRequest->update(['user_id' => $user->id]);
        $this->linkContact($consultationRequest, $user);

        $user->notify(new DashboardAccessNotification);

        return $user;
    }

    /**
     * Attach the now-provisioned client account to the CRM contact behind this
     * consultation, so the prospect record and the login account are one person.
     */
    private function linkContact(ConsultationRequest $consultationRequest, User $user): void
    {
        // For a guest/plan checkout there is no contact yet — create one enriched
        // with the details the buyer entered (company/country/phone), not just
        // their name/email.
        $contact = $consultationRequest->contact
            ?? Contact::resolve([
                'email' => $consultationRequest->email,
                'name' => $consultationRequest->name,
                'company' => $consultationRequest->company,
                'country' => $consultationRequest->country,
                'phone' => $consultationRequest->phone,
            ]);

        if ($contact->user_id !== $user->id) {
            $contact->update(['user_id' => $user->id]);
        }

        if ($consultationRequest->contact_id !== $contact->id) {
            $consultationRequest->update(['contact_id' => $contact->id]);
        }
    }
}
