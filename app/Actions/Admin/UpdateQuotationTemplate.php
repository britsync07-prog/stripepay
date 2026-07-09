<?php

namespace App\Actions\Admin;

use App\Models\QuotationTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateQuotationTemplate
{
    use AsAction;

    public function asController(Request $request, QuotationTemplate $quotationTemplate)
    {
        $data = $request->validate(StoreQuotationTemplate::rules());

        $quotationTemplate->update(StoreQuotationTemplate::payload($data));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Template updated.']);

        return redirect()->route('admin.quotation-templates');
    }
}
