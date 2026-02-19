<?php

namespace App\Livewire\Admin\Analytics;

use App\Models\AnalyticsSnapshot;
use App\Services\Analytics\AnalyticsSnapshotService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app.sidebar')]
class Index extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('access-admin');
    }

    public function render(AnalyticsSnapshotService $snapshotService)
    {
        $weekSnapshot = $snapshotService->getOrCreateSnapshot(AnalyticsSnapshot::PERIOD_WEEK, now());
        $monthSnapshot = $snapshotService->getOrCreateSnapshot(AnalyticsSnapshot::PERIOD_MONTH, now());

        $weeklyTrend = $this->formatTrend($snapshotService->recentSnapshots(AnalyticsSnapshot::PERIOD_WEEK, 8));
        $monthlyTrend = $this->formatTrend($snapshotService->recentSnapshots(AnalyticsSnapshot::PERIOD_MONTH, 8));

        return view('livewire.admin.analytics.index', [
            'sections' => [
                [
                    'title' => 'Weekly Performance',
                    'description' => sprintf(
                        '%s to %s',
                        $weekSnapshot->period_start->format('M d, Y'),
                        $weekSnapshot->period_end->format('M d, Y')
                    ),
                    'metrics' => $weekSnapshot->metrics,
                    'trend' => $weeklyTrend,
                ],
                [
                    'title' => 'Monthly Performance',
                    'description' => $monthSnapshot->period_start->format('F Y'),
                    'metrics' => $monthSnapshot->metrics,
                    'trend' => $monthlyTrend,
                ],
            ],
        ]);
    }

    /**
     * @param  Collection<int, AnalyticsSnapshot>  $snapshots
     * @return array<string, array<int, float|string|int>>
     */
    private function formatTrend(Collection $snapshots): array
    {
        $labels = [];
        $requestTotals = [];
        $requestConversionRates = [];
        $revenueEstimates = [];
        $occupancyRates = [];
        $openTickets = [];
        $closedTickets = [];

        foreach ($snapshots as $snapshot) {
            $metrics = $snapshot->metrics ?? [];
            $viewingMetrics = $metrics['viewing_requests'] ?? [];
            $rentalMetrics = $metrics['rentals'] ?? [];
            $ticketMetrics = $metrics['tickets'] ?? [];

            $labels[] = $snapshot->period_start->format('M d');
            $requestTotals[] = (int) ($viewingMetrics['total'] ?? 0);
            $requestConversionRates[] = (float) ($viewingMetrics['conversion_rate'] ?? 0);
            $revenueEstimates[] = (float) ($rentalMetrics['revenue_estimate'] ?? 0);
            $occupancyRates[] = (float) ($rentalMetrics['occupancy_rate'] ?? 0);
            $openTickets[] = (int) ($ticketMetrics['open'] ?? 0);
            $closedTickets[] = (int) ($ticketMetrics['closed'] ?? 0);
        }

        return [
            'labels' => $labels,
            'request_totals' => $requestTotals,
            'request_conversion_rates' => $requestConversionRates,
            'revenue_estimates' => $revenueEstimates,
            'occupancy_rates' => $occupancyRates,
            'open_tickets' => $openTickets,
            'closed_tickets' => $closedTickets,
        ];
    }
}

