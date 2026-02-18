<?php

namespace App\Livewire\Public;

use App\Models\Category;
use App\Models\Rental;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.public')]
class ShowroomIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'category', history: true)]
    public string $categoryFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $now = CarbonImmutable::now();

        $units = Unit::query()
            ->with([
                'category',
                'images' => fn ($query) => $query->orderBy('sort_order'),
            ])
            ->withExists([
                'rentals as has_active_rental' => fn ($query) => $query
                    ->where('status', Rental::STATUS_ACTIVE)
                    ->where('starts_at', '<=', $now)
                    ->where('ends_at', '>=', $now),
                'rentals as has_upcoming_rental' => fn ($query) => $query
                    ->where('status', Rental::STATUS_ACTIVE)
                    ->where('starts_at', '>', $now),
            ])
            ->withMax([
                'rentals as active_rental_ends_at' => fn ($query) => $query
                    ->where('status', Rental::STATUS_ACTIVE)
                    ->where('starts_at', '<=', $now)
                    ->where('ends_at', '>=', $now),
            ], 'ends_at')
            ->withMin([
                'rentals as next_rental_starts_at' => fn ($query) => $query
                    ->where('status', Rental::STATUS_ACTIVE)
                    ->where('starts_at', '>', $now),
            ], 'starts_at')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($inner): void {
                    $inner->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('location', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->categoryFilter !== '', function ($query): void {
                $query->where('category_id', $this->categoryFilter);
            })
            ->orderByRaw(
                'case when has_active_rental = 1 then 1 else 0 end',
            )
            ->orderByRaw(
                'case when status = ? then 0 else 1 end',
                [Unit::STATUS_AVAILABLE]
            )
            ->latest('created_at')
            ->paginate(12);

        return view('livewire.public.showroom-index', [
            'units' => $units,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }
}
