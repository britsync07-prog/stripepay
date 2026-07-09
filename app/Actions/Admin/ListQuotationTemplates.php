<?php

namespace App\Actions\Admin;

use App\Data\Quotation\QuotationTemplateData;
use App\Models\QuotationTemplate;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ListQuotationTemplates
{
    use AsAction;

    public function asController()
    {
        return Inertia::render('admin/quotation-templates/index', [
            'templates' => QuotationTemplateData::collect(
                QuotationTemplate::query()->latest()->get()
            ),
        ]);
    }
}
