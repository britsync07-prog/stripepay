<?php

namespace App\Actions\Admin;

use App\Data\ConsultationRequest\ConsultationData;
use App\Models\ConsultationRequest;
use App\Support\DashboardMetrics;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ShowAdminDashboard
{
    use AsAction;

    public function asController(Request $request)
    {
        if (! $request->user()->hasRole('admin')) {
            return redirect()->route('portal.dashboard');
        }

        $range = (string) $request->query('range', 'all');

        if (! array_key_exists($range, DashboardMetrics::RANGES)) {
            $range = 'all';
        }

        $since = DashboardMetrics::sinceForRange($range);
        $seriesMonths = DashboardMetrics::RANGES[$range] ?? 12;

        return Inertia::render('admin/dashboard', [
            'range' => $range,
            'stats' => DashboardMetrics::stats(),
            'needsAttention' => DashboardMetrics::needsAttention(),
            'leadsPipeline' => DashboardMetrics::leadsPipeline(),
            'composition' => DashboardMetrics::financialComposition($since),
            'gatewayBreakdown' => DashboardMetrics::paymentsByGateway($since),
            'revenueByPlan' => Inertia::defer(fn () => DashboardMetrics::revenueByPlan($since), 'insights'),
            'revenueSeries' => Inertia::defer(fn () => DashboardMetrics::revenueSeries($seriesMonths), 'insights'),
            'countries' => Inertia::defer(fn () => DashboardMetrics::topCountries(), 'insights'),
            'recent' => Inertia::defer(fn () => ConsultationData::collect(
                ConsultationRequest::with('plan', 'expert')->latest()->limit(6)->get()
            ), 'insights'),
        ]);
    }
}
