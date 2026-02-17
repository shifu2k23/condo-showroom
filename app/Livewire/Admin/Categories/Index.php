<?php

namespace App\Livewire\Admin\Categories;

use App\Models\Category;
use App\Services\AuditLogger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app.sidebar')]
class Index extends Component
{
    use AuthorizesRequests;

    public string $name = '';

    public ?int $editingCategoryId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Category::class);
    }

    public function save(AuditLogger $auditLogger): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $slug = $this->uniqueSlug($validated['name']);

        if ($this->editingCategoryId) {
            $category = Category::query()->findOrFail($this->editingCategoryId);
            $this->authorize('update', $category);
            $old = $category->only(['name', 'slug']);

            $category->update([
                'name' => $validated['name'],
                'slug' => $slug,
            ]);

            $auditLogger->log(
                action: 'CATEGORY_UPDATED',
                changes: ['before' => $old, 'after' => $category->only(['name', 'slug'])]
            );
        } else {
            $this->authorize('create', Category::class);

            $category = Category::create([
                'name' => $validated['name'],
                'slug' => $slug,
            ]);

            $auditLogger->log(
                action: 'CATEGORY_CREATED',
                changes: $category->only(['id', 'name', 'slug'])
            );
        }

        $this->reset(['name', 'editingCategoryId']);
    }

    public function edit(int $categoryId): void
    {
        $category = Category::query()->findOrFail($categoryId);
        $this->authorize('update', $category);

        $this->editingCategoryId = $category->id;
        $this->name = $category->name;
    }

    public function cancelEdit(): void
    {
        $this->reset(['name', 'editingCategoryId']);
    }

    public function delete(int $categoryId, AuditLogger $auditLogger): void
    {
        $category = Category::query()->withCount('units')->findOrFail($categoryId);
        $this->authorize('delete', $category);

        if ($category->units_count > 0) {
            $this->addError('name', 'Cannot delete category with existing units.');

            return;
        }

        $auditLogger->log(
            action: 'CATEGORY_DELETED',
            changes: $category->only(['id', 'name', 'slug'])
        );

        $category->delete();
    }

    public function render()
    {
        return view('livewire.admin.categories.index', [
            'categories' => Category::query()->withCount('units')->orderBy('name')->get(),
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $counter = 1;

        while (
            Category::query()
                ->where('slug', $slug)
                ->when($this->editingCategoryId, fn ($query) => $query->whereKeyNot($this->editingCategoryId))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
