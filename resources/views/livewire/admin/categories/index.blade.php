<div class="space-y-7">
    <div class="flex flex-col gap-2">
        <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Categories</h2>
        <p class="text-sm text-slate-500">Manage showroom category taxonomy.</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <form wire:submit.prevent="save" class="flex flex-col gap-3 md:flex-row md:items-end">
            <div class="w-full">
                <label for="category-name" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Category Name</label>
                <input id="category-name" type="text" wire:model="name" required class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-indigo-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50">{{ $editingCategoryId ? 'Update' : 'Add' }}</button>
                @if($editingCategoryId)
                    <button type="button" wire:click="cancelEdit" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:-translate-y-0.5 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50">Cancel</button>
                @endif
            </div>
        </form>

        @error('name')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-[0.14em] text-slate-500">
                    <th scope="col" class="px-4 py-3 font-medium">Name</th>
                    <th scope="col" class="px-4 py-3 font-medium">Slug</th>
                    <th scope="col" class="px-4 py-3 font-medium">Units</th>
                    <th scope="col" class="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr class="border-b border-slate-200 text-slate-700 transition duration-150 hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $category->name }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $category->slug }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $category->units_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap justify-end gap-2">
                                <button type="button" wire:click="edit({{ $category->id }})" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 transition hover:-translate-y-0.5 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">Edit</button>

                                @if($category->units_count > 0)
                                    <button type="button" disabled aria-disabled="true" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-200 bg-slate-100 px-3 text-xs font-medium text-slate-400">Delete</button>
                                @else
                                    <button type="button" wire:click="delete({{ $category->id }})" wire:confirm="Delete this category?" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-semibold text-red-700 transition hover:-translate-y-0.5 hover:bg-red-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">Delete</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">No categories yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
