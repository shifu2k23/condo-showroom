<?php

namespace App\Livewire\Admin\ViewingRequests;

use App\Models\Unit;
use App\Models\ViewingRequest;
use App\Services\ViewingRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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

    public function mount(): void
    {
        $this->authorize('viewAny', ViewingRequest::class);
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

    public function render()
    {
        $query = ViewingRequest::query()
            ->with('unit')
            ->when($this->statusFilter !== '', fn ($builder) => $builder->where('status', $this->statusFilter))
            ->when($this->unitFilter !== '', fn ($builder) => $builder->where('unit_id', $this->unitFilter))
            ->when($this->dateFrom, fn ($builder) => $builder->whereDate('requested_start_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($builder) => $builder->whereDate('requested_start_at', '<=', $this->dateTo))
            ->orderByDesc('requested_start_at');

        return view('livewire.admin.viewing-requests.index', [
            'viewingRequests' => $query->paginate(15),
            'units' => Unit::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
