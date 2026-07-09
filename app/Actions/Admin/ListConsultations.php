<?php

namespace App\Actions\Admin;

use App\Data\ConsultationRequest\ConsultationData;
use App\Enums\ConsultationStatus;
use App\Models\ConsultationRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ListConsultations
{
    use AsAction;

    public function asController(Request $request)
    {
        $consultations = QueryBuilder::for(ConsultationRequest::class)
            ->with('plan', 'expert')
            ->allowedFilters(...[
                AllowedFilter::exact('status'),
                // Separate unpriced custom leads ("requests") from real
                // consultations (plan-based, or a custom offer already priced).
                AllowedFilter::callback('type', function ($query, string $value) {
                    if ($value === 'requests') {
                        $query->whereNull('plan_id')->where('amount', '<=', 0);
                    } elseif ($value === 'consultations') {
                        $query->where(fn ($q) => $q->whereNotNull('plan_id')->orWhere('amount', '>', 0));
                    }
                }),
                AllowedFilter::callback('assigned', function ($query, string $value) {
                    if ($value === 'no') {
                        $query->whereNull('expert_id');
                    } elseif ($value === 'yes') {
                        $query->whereNotNull('expert_id');
                    }
                }),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('reference', 'like', "%{$value}%")
                            ->orWhere('name', 'like', "%{$value}%")
                            ->orWhere('email', 'like', "%{$value}%")
                            ->orWhere('title', 'like', "%{$value}%")
                            ->orWhereHas('plan', fn ($q) => $q->where('name', 'like', "%{$value}%"));
                    });
                }),
            ])
            ->defaultSort('-created_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('admin/consultations', [
            'consultations' => $consultations->through(fn (ConsultationRequest $c) => ConsultationData::fromModel($c)),
            'filters' => [
                'search' => (string) $request->input('filter.search', ''),
                'status' => (string) $request->input('filter.status', ''),
                'type' => (string) $request->input('filter.type', ''),
                'assigned' => (string) $request->input('filter.assigned', ''),
            ],
            'statusOptions' => ConsultationStatus::options(),
            'stats' => [
                'total' => ConsultationRequest::count(),
                'requests' => ConsultationRequest::whereNull('plan_id')->where('amount', '<=', 0)
                    ->where('status', ConsultationStatus::Pending->value)->count(),
                'pending' => ConsultationRequest::where('status', ConsultationStatus::Pending->value)->count(),
            ],
        ]);
    }
}
