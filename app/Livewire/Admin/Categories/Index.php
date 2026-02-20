<?php

namespace App\Livewire\Admin\Categories;

use App\Models\Category;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
        $tenantId = (int) (auth()->user()?->tenant_id ?? 0);
        $nameUniqueRule = Rule::unique((new Category())->getTable(), 'name')
            ->where(fn ($query) => $query->where('tenant_id', $tenantId));

        if ($this->editingCategoryId !== null) {
            $nameUniqueRule = $nameUniqueRule->ignore($this->editingCategoryId);
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', $nameUniqueRule],
        ], [
            'name.unique' => 'Category name already exists.',
        ]);

        $slug = $this->uniqueSlug($validated['name']);

        try {
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
        } catch (QueryException $exception) {
            if ($this->isDuplicateKeyViolation($exception)) {
                $this->addError('name', 'Category name already exists.');

                return;
            }

            throw $exception;
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

    private function isDuplicateKeyViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        if ($sqlState === '23000' || $sqlState === '23505') {
            return true;
        }

        return str_contains(strtolower($exception->getMessage()), 'duplicate');
    }
}
