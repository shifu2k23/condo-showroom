<div class="space-y-5 p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Units</h1>
        <flux:button variant="primary" :href="route('admin.units.create')" wire:navigate>Add Unit</flux:button>
    </div>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
        <flux:input wire:model.live.debounce.350ms="search" icon="magnifying-glass" placeholder="Search units..." />
        <flux:select wire:model.live="statusFilter">
            <option value="">All statuses</option>
            <option value="{{ \App\Models\Unit::STATUS_AVAILABLE }}">Available</option>
            <option value="{{ \App\Models\Unit::STATUS_UNAVAILABLE }}">Unavailable</option>
        </flux:select>
        <flux:select wire:model.live="categoryFilter">
            <option value="">All categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </flux:select>
        <label class="flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
            <input type="checkbox" wire:model.live="showTrashed">
            Include deleted
        </label>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">Unit</th>
                    <th class="px-4 py-3 text-left font-medium">Category</th>
                    <th class="px-4 py-3 text-left font-medium">Status</th>
                    <th class="px-4 py-3 text-left font-medium">Price</th>
                    <th class="px-4 py-3 text-left font-medium">Deleted</th>
                    <th class="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($units as $unit)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $unit->name }}</p>
                            <p class="text-xs text-zinc-500">{{ $unit->location ?: 'No location' }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $unit->category->name }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $unit->status === \App\Models\Unit::STATUS_AVAILABLE ? 'bg-green-100 text-green-700' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' }}">
                                {{ $unit->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($unit->price_display_mode === \App\Models\Unit::DISPLAY_MONTH && $unit->monthly_price_php)
                                &#8369;{{ number_format($unit->monthly_price_php) }}/month
                            @elseif($unit->nightly_price_php)
                                &#8369;{{ number_format($unit->nightly_price_php) }}/night
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $unit->deleted_at ? $unit->deleted_at->format('Y-m-d H:i') : '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <flux:button size="xs" variant="ghost" :href="route('admin.units.edit', $unit)" wire:navigate>Edit</flux:button>
                                @if(!$unit->trashed())
                                    @if($unit->status !== \App\Models\Unit::STATUS_AVAILABLE)
                                        <flux:button size="xs" variant="ghost" wire:click="setAvailable({{ $unit->id }})">Set Available</flux:button>
                                    @endif
                                    @if($unit->status !== \App\Models\Unit::STATUS_UNAVAILABLE)
                                        <flux:button size="xs" variant="ghost" wire:click="setUnavailable({{ $unit->id }})">Set Unavailable</flux:button>
                                    @endif
                                    <flux:button size="xs" variant="danger" wire:click="deleteUnit({{ $unit->id }})" wire:confirm="Soft delete this unit?">Delete</flux:button>
                                @else
                                    <flux:button size="xs" variant="primary" wire:click="restoreUnit({{ $unit->id }})">Restore</flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-zinc-500">No units found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $units->links() }}</div>
</div>
