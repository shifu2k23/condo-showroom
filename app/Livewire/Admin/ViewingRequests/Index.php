<?php

namespace App\Livewire\Admin\ViewingRequests;

use App\Models\Unit;
use App\Models\ViewingRequest;
use App\Services\ViewingRequestService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app.sidebar')]
class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    #[Url(as: 'status', history: true)]
    public string $statusFilter = '';

    #[Url(as: 'unit', history: true)]
    public string $unitFilter = '';

    #[Url(as: 'from', history: true)]
    public ?string $dateFrom = null;

    #[Url(as: 'to', history: true)]
    public ?string $dateTo = null;

    #[Url(as: 'month', history: true)]
    public string $calendarMonth = '';

    public function mount(): void
    {
        $this->authorize('viewAny', ViewingRequest::class);

        $this->calendarMonth = $this->normalizeCalendarMonth($this->calendarMonth);
        $this->dateFrom = $this->normalizeDate($this->dateFrom);
        $this->dateTo = $this->normalizeDate($this->dateTo);
    }

    public function confirmRequest(int $requestId, ViewingRequestService $service): void
    {
        $request = ViewingRequest::query()->findOrFail($requestId);
        $this->authorize('confirm', $request);

        $service->confirm($request, request());
    }

    public function cancelRequest(int $requestId, ViewingRequestService $service): void
    {
        $request = ViewingRequest::query()->findOrFail($requestId);
        $this->authorize('cancel', $request);

        $service->cancel($request, request());
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedUnitFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->dateFrom = $this->normalizeDate($this->dateFrom);
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->dateTo = $this->normalizeDate($this->dateTo);
        $this->resetPage();
    }

    public function previousCalendarMonth(): void
    {
        $this->calendarMonth = $this->calendarMonthDate()
            ->subMonthNoOverflow()
            ->format('Y-m');
    }

    public function nextCalendarMonth(): void
    {
        $this->calendarMonth = $this->calendarMonthDate()
            ->addMonthNoOverflow()
            ->format('Y-m');
    }

    public function jumpToCurrentMonth(): void
    {
        $this->calendarMonth = now()->format('Y-m');
    }

    public function selectCalendarDay(string $date): void
    {
        $normalizedDate = $this->normalizeDate($date);
        if ($normalizedDate === null) {
            return;
        }

        $this->dateFrom = $normalizedDate;
        $this->dateTo = $normalizedDate;
        $this->resetPage();
    }

    public function clearDateRange(): void
    {
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->resetPage();
    }

    public function render()
    {
        $calendarMonth = $this->calendarMonthDate();
        $visibleStart = $calendarMonth->startOfMonth()->startOfWeek(CarbonInterface::MONDAY);
        $visibleEnd = $calendarMonth->endOfMonth()->endOfWeek(CarbonInterface::SUNDAY);
        $query = $this->filteredQuery();

        $calendarRequests = (clone $query)
            ->whereBetween('requested_start_at', [
                $visibleStart->startOfDay(),
                $visibleEnd->endOfDay(),
            ])
            ->orderBy('requested_start_at')
            ->get([
                'id',
                'unit_id',
                'requester_name',
                'status',
                'requested_start_at',
            ]);

        $dailySummary = $this->buildCalendarDailySummary($calendarRequests);
        $calendarWeeks = $this->buildCalendarGrid($calendarMonth, $dailySummary);
        $calendarStatusSummary = [
            'total' => $calendarRequests->count(),
            'pending' => $calendarRequests->where('status', ViewingRequest::STATUS_PENDING)->count(),
            'confirmed' => $calendarRequests->where('status', ViewingRequest::STATUS_CONFIRMED)->count(),
            'cancelled' => $calendarRequests->where('status', ViewingRequest::STATUS_CANCELLED)->count(),
        ];

        $upcomingRequests = (clone $query)
            ->where('requested_start_at', '>=', now())
            ->orderBy('requested_start_at')
            ->limit(8)
            ->get([
                'id',
                'unit_id',
                'requester_name',
                'requested_start_at',
                'requested_end_at',
                'status',
            ]);

        return view('livewire.admin.viewing-requests.index', [
            'viewingRequests' => (clone $query)
                ->orderByDesc('requested_start_at')
                ->paginate(15),
            'units' => Unit::query()->orderBy('name')->get(['id', 'name']),
            'calendarMonthLabel' => $calendarMonth->format('F Y'),
            'calendarWeeks' => $calendarWeeks,
            'calendarStatusSummary' => $calendarStatusSummary,
            'upcomingRequests' => $upcomingRequests,
        ]);
    }

    private function filteredQuery(): Builder
    {
        return ViewingRequest::query()
            ->with('unit:id,name')
            ->when($this->statusFilter !== '', fn (Builder $builder) => $builder->where('status', $this->statusFilter))
            ->when($this->unitFilter !== '', fn (Builder $builder) => $builder->where('unit_id', $this->unitFilter))
            ->when($this->dateFrom, fn (Builder $builder) => $builder->whereDate('requested_start_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $builder) => $builder->whereDate('requested_start_at', '<=', $this->dateTo));
    }

    private function buildCalendarDailySummary(Collection $requests): array
    {
        return $requests
            ->groupBy(fn (ViewingRequest $request): string => $request->requested_start_at->toDateString())
            ->map(function (Collection $dayRequests): array {
                $preview = $dayRequests
                    ->take(2)
                    ->map(function (ViewingRequest $request): array {
                        return [
                            'id' => $request->id,
                            'requester' => $request->requester_name,
                            'time' => $request->requested_start_at->format('h:i A'),
                            'status' => $request->status,
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'total' => $dayRequests->count(),
                    'pending' => $dayRequests->where('status', ViewingRequest::STATUS_PENDING)->count(),
                    'confirmed' => $dayRequests->where('status', ViewingRequest::STATUS_CONFIRMED)->count(),
                    'cancelled' => $dayRequests->where('status', ViewingRequest::STATUS_CANCELLED)->count(),
                    'preview' => $preview,
                ];
            })
            ->all();
    }

    /**
     * @param array<string, array{total:int,pending:int,confirmed:int,cancelled:int,preview:array<int, array{id:int,requester:string,time:string,status:string}>}> $dailySummary
     * @return array<int, array<int, array{date:string,dayNumber:int,inMonth:bool,isToday:bool,isInActiveRange:bool,isSelectedDay:bool,summary:array{total:int,pending:int,confirmed:int,cancelled:int,preview:array<int, array{id:int,requester:string,time:string,status:string}>}}>>
     */
    private function buildCalendarGrid(CarbonImmutable $calendarMonth, array $dailySummary): array
    {
        $start = $calendarMonth->startOfMonth()->startOfWeek(CarbonInterface::MONDAY);
        $end = $calendarMonth->endOfMonth()->endOfWeek(CarbonInterface::SUNDAY);
        $cursor = $start;
        $today = CarbonImmutable::today();
        $weeks = [];
        $week = [];

        while ($cursor->lessThanOrEqualTo($end)) {
            $dateKey = $cursor->toDateString();
            $isSelectedDay = $this->dateFrom !== null
                && $this->dateTo !== null
                && $this->dateFrom === $dateKey
                && $this->dateTo === $dateKey;

            $week[] = [
                'date' => $dateKey,
                'dayNumber' => (int) $cursor->day,
                'inMonth' => $cursor->isSameMonth($calendarMonth),
                'isToday' => $cursor->isSameDay($today),
                'isInActiveRange' => $this->isInActiveDateRange($dateKey),
                'isSelectedDay' => $isSelectedDay,
                'summary' => $dailySummary[$dateKey] ?? $this->emptyDailySummary(),
            ];

            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }

            $cursor = $cursor->addDay();
        }

        return $weeks;
    }

    /**
     * @return array{total:int,pending:int,confirmed:int,cancelled:int,preview:array<int, array{id:int,requester:string,time:string,status:string}>}
     */
    private function emptyDailySummary(): array
    {
        return [
            'total' => 0,
            'pending' => 0,
            'confirmed' => 0,
            'cancelled' => 0,
            'preview' => [],
        ];
    }

    private function isInActiveDateRange(string $date): bool
    {
        if ($this->dateFrom === null && $this->dateTo === null) {
            return false;
        }

        $from = $this->dateFrom ?? $this->dateTo;
        $to = $this->dateTo ?? $this->dateFrom;

        if ($from === null || $to === null) {
            return false;
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return $date >= $from && $date <= $to;
    }

    private function calendarMonthDate(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m', $this->normalizeCalendarMonth($this->calendarMonth))
            ->startOfMonth();
    }

    private function normalizeCalendarMonth(?string $month): string
    {
        if (! is_string($month) || trim($month) === '') {
            return now()->format('Y-m');
        }

        try {
            $parsed = CarbonImmutable::createFromFormat('Y-m', $month);
        } catch (\Throwable) {
            return now()->format('Y-m');
        }

        return $parsed->format('Y-m');
    }

    private function normalizeDate(?string $date): ?string
    {
        if (! is_string($date) || trim($date) === '') {
            return null;
        }

        try {
            $parsed = CarbonImmutable::createFromFormat('Y-m-d', $date);
        } catch (\Throwable) {
            return null;
        }

        return $parsed->format('Y-m-d');
    }
}
