<?php

namespace App\Actions\Clients;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;

class MagicLogin
{
    use AsAction;

    public function asController(Request $request, User $user)
    {

        if (is_null($user->email_verified_at)) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('portal.dashboard');
    }
}
