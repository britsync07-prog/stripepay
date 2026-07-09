<?php

namespace App\Actions\Admin;

use App\Data\Quotation\QuotationTemplateData;
use App\Models\QuotationTemplate;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/** Renders the create/edit form for a quotation template. */
class ShowQuotationTemplateForm
{
    use AsAction;

    public function asController(?QuotationTemplate $quotationTemplate = null)
    {
        return Inertia::render('admin/quotation-templates/form', [
            'template' => $quotationTemplate
                ? QuotationTemplateData::fromModel($quotationTemplate)
                : null,
        ]);
    }
}
