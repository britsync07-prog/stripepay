<?php

namespace App\Actions\Admin;

use App\Models\Expert;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateExpert
{
    use AsAction;

    public function asController(Request $request, Expert $expert)
    {
        $data = $request->validate(StoreExpert::rules($expert->id));

        $expert->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expert updated.']);

        return redirect()->route('admin.experts');
    }
}
