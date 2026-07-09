<?php

namespace App\Actions\Admin;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Admin-side create-or-update of a client. Reuses the account by email (never a
 * duplicate), ensures the `client` role, and links/enriches the CRM contact.
 *
 * Deliberately leaves the email **unverified** and sends nothing: an admin
 * vouching for a client is not proof of email ownership, so portal access is
 * still earned by payment (or an explicitly sent access link) — this keeps bulk
 * admin creation/import from minting live login accounts for unverified emails.
 *
 * @return array{user: User, created: bool}
 */
class UpsertClient
{
    use AsAction;

    /**
     * @param  array{email: string, name?: ?string, company?: ?string, country?: ?string, phone?: ?string}  $attributes
     */
    public function handle(array $attributes): array
    {
        $user = User::firstOrNew(['email' => $attributes['email']]);
        $created = ! $user->exists;

        if ($created) {
            $user->name = $attributes['name'] ?: 'Client';
            $user->password = Hash::make(Str::random(40));
            $user->save();
        } elseif (! empty($attributes['name'])) {
            $user->update(['name' => $attributes['name']]);
        }

        if (! $user->hasRole('client')) {
            $user->assignRole('client');
        }

        $contact = Contact::resolve([
            'email' => $attributes['email'],
            'name' => $attributes['name'] ?? $user->name,
            'company' => $attributes['company'] ?? null,
            'country' => $attributes['country'] ?? null,
            'phone' => $attributes['phone'] ?? null,
        ]);

        if ($contact->user_id !== $user->id) {
            $contact->update(['user_id' => $user->id]);
        }

        return ['user' => $user, 'created' => $created];
    }
}
