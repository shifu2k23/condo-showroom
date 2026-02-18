<?php

namespace App\Livewire\Admin\Rentals;

use App\Models\Rental;
use App\Models\Unit;
use App\Services\AuditLogger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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

    #[Url(as: 'unit', history: true)]
    public string $unitFilter = '';

    public ?string $issuedRentalCode = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Rental::class);
        $this->issuedRentalCode = session()->pull('issued_rental_code');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingUnitFilter(): void
    {
        $this->resetPage();
    }

    public function deleteRental(int $rentalId, AuditLogger $auditLogger): void
    {
        $rental = Rental::query()->with('unit')->findOrFail($rentalId);
        $this->authorize('delete', $rental);

        $auditLogger->log(
            action: 'RENTAL_DELETED',
            unit: $rental->unit,
            changes: [
                'rental_id' => $rental->id,
                'renter_name' => $rental->renter_name,
                'id_type' => $rental->id_type,
                'public_code_last4' => $rental->public_code_last4,
                'starts_at' => optional($rental->starts_at)->toDateTimeString(),
                'ends_at' => optional($rental->ends_at)->toDateTimeString(),
                'status' => $rental->status,
            ]
        );

        $rental->delete();

        session()->flash('status', 'Rental deleted successfully.');
    }

    public function render()
    {
        $query = Rental::query()
            ->with('unit')
            ->when($this->search !== '', function ($builder): void {
                $builder->where('renter_name', 'like', '%'.$this->search.'%');
            })
            ->when($this->statusFilter !== '', fn ($builder) => $builder->where('status', $this->statusFilter))
            ->when($this->unitFilter !== '', fn ($builder) => $builder->where('unit_id', $this->unitFilter))
            ->orderByDesc('starts_at');

        return view('livewire.admin.rentals.index', [
            'rentals' => $query->paginate(15),
            'units' => Unit::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
