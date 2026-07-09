<?php

namespace App\Actions\Admin;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ReorderPlans
{
    use AsAction;

    public function asController(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:plans,id'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['ids'] as $position => $id) {
                Plan::whereKey($id)->update(['sort_order' => $position + 1]);
            }
        });

        return back();
    }
}
