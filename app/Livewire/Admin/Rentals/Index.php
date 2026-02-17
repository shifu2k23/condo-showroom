<?php

namespace App\Livewire\Admin\Rentals;

use App\Models\Rental;
use App\Models\Unit;
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
