<?php

namespace App\Actions\Clients;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ShowClientProfile
{
    use AsAction;

    public function asController(Request $request)
    {
        $user = $request->user();

        return Inertia::render('portal/profile', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
