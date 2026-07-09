<?php

namespace App\Actions\Admin;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Edit a client's details (name/email + CRM contact company/country/phone).
 */
class UpdateClient
{
    use AsAction;

    public function asController(Request $request, User $client)
    {
        abort_unless($client->hasRole('client'), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($client->id)],
            'company' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:2'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $client->update(['name' => $data['name'], 'email' => $data['email']]);

        $contact = Contact::resolve([
            'email' => $data['email'],
            'name' => $data['name'],
            'company' => $data['company'] ?? null,
            'country' => $data['country'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);

        if ($contact->user_id !== $client->id) {
            $contact->update(['user_id' => $client->id]);
        }

        activity('consultation')
            ->causedBy($request->user())
            ->performedOn($client)
            ->withProperties(['type' => 'client_updated', 'email' => $client->email])
            ->log('Client updated');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Client updated.']);

        return back();
    }
}
