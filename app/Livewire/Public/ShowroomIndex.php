<?php

namespace App\Livewire\Public;

use App\Models\Category;
use App\Models\Unit;
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
        $units = Unit::query()
            ->with([
                'category',
                'images' => fn ($query) => $query->orderBy('sort_order'),
            ])
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
