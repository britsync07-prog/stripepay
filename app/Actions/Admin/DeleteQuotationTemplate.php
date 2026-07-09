<?php

namespace App\Actions\Admin;

use App\Models\QuotationTemplate;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteQuotationTemplate
{
    use AsAction;

    public function asController(QuotationTemplate $quotationTemplate)
    {
        $quotationTemplate->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Template deleted.']);

        return back();
    }
}
