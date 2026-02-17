<div class="space-y-5 p-6">
    <h1 class="text-2xl font-semibold">Categories</h1>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <form wire:submit.prevent="save" class="flex flex-col gap-3 md:flex-row md:items-end">
            <flux:input wire:model="name" label="Category Name" required class="w-full" />
            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">
                    {{ $editingCategoryId ? 'Update' : 'Add' }}
                </flux:button>
                @if($editingCategoryId)
                    <flux:button type="button" variant="ghost" wire:click="cancelEdit">Cancel</flux:button>
                @endif
            </div>
        </form>
        @error('name')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">Name</th>
                    <th class="px-4 py-3 text-left font-medium">Slug</th>
                    <th class="px-4 py-3 text-left font-medium">Units</th>
                    <th class="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($categories as $category)
                    <tr>
                        <td class="px-4 py-3">{{ $category->name }}</td>
                        <td class="px-4 py-3 text-zinc-500">{{ $category->slug }}</td>
                        <td class="px-4 py-3">{{ $category->units_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <flux:button size="xs" variant="ghost" wire:click="edit({{ $category->id }})">Edit</flux:button>
                                @if($category->units_count > 0)
                                    <flux:button size="xs" variant="danger" disabled>Delete</flux:button>
                                @else
                                    <flux:button
                                        size="xs"
                                        variant="danger"
                                        wire:click="delete({{ $category->id }})"
                                        wire:confirm="Delete this category?"
                                    >
                                        Delete
                                    </flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-zinc-500">No categories yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
