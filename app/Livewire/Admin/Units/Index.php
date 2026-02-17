<?php

namespace App\Livewire\Admin\Units;

use App\Models\Category;
use App\Models\Unit;
use App\Services\AuditLogger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app.sidebar')]
class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'status', history: true)]
    public string $statusFilter = '';

    #[Url(as: 'category', history: true)]
    public string $categoryFilter = '';

    #[Url(as: 'trashed', history: true)]
    public bool $showTrashed = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Unit::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingShowTrashed(): void
    {
        $this->resetPage();
    }

    public function setAvailable(int $unitId): void
    {
        $this->setStatus($unitId, Unit::STATUS_AVAILABLE);
    }

    public function setUnavailable(int $unitId): void
    {
        $this->setStatus($unitId, Unit::STATUS_UNAVAILABLE);
    }

    public function deleteUnit(int $unitId): void
    {
        $unit = Unit::query()->findOrFail($unitId);
        $this->authorize('delete', $unit);

        $unit->delete();

        app(AuditLogger::class)->log(
            action: 'UNIT_SOFT_DELETED',
            unit: $unit,
            changes: ['status' => $unit->status]
        );
    }

    public function restoreUnit(int $unitId): void
    {
        $unit = Unit::withTrashed()->findOrFail($unitId);
        $this->authorize('restore', $unit);

        $unit->restore();

        app(AuditLogger::class)->log(
            action: 'UNIT_RESTORED',
            unit: $unit,
            changes: ['status' => $unit->status]
        );
    }

    public function render()
    {
        $unitsQuery = Unit::query()
            ->with(['category', 'images'])
            ->when($this->showTrashed, fn ($query) => $query->withTrashed())
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($inner): void {
                    $inner->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('location', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->categoryFilter !== '', fn ($query) => $query->where('category_id', $this->categoryFilter))
            ->latest('created_at');

        return view('livewire.admin.units.index', [
            'units' => $unitsQuery->paginate(12),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    private function setStatus(int $unitId, string $status): void
    {
        DB::transaction(function () use ($unitId, $status): void {
            $unit = Unit::query()->whereKey($unitId)->lockForUpdate()->firstOrFail();
            $this->authorize('setAvailability', $unit);

            $oldStatus = $unit->status;

            if ($oldStatus === $status) {
                return;
            }

            $unit->update([
                'status' => $status,
                'updated_by' => auth()->id(),
            ]);

            app(AuditLogger::class)->log(
                action: $status === Unit::STATUS_AVAILABLE ? 'SET_AVAILABLE' : 'SET_UNAVAILABLE',
                unit: $unit,
                changes: [
                    'old_status' => $oldStatus,
                    'new_status' => $status,
                ]
            );
        });
    }
}
