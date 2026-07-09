<?php

namespace App\Actions\Admin;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Admin adds a client by hand. Creates (or reuses by email) the client account
 * and its CRM contact via {@see UpsertClient}, then logs the mutation.
 */
class StoreClient
{
    use AsAction;

    public function asController(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:2'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        ['user' => $user, 'created' => $created] = UpsertClient::run($data);

        activity('consultation')
            ->causedBy($request->user())
            ->performedOn($user)
            ->withProperties(['type' => $created ? 'client_created' : 'client_updated', 'email' => $user->email])
            ->log($created ? 'Client added manually' : 'Existing client updated');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $created ? 'Client added.' : 'That email already existed — the client was updated.',
        ]);

        return back();
    }
}
