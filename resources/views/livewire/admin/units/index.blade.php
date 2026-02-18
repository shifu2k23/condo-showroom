<div class="space-y-7">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Units</h2>
        <a href="{{ route('admin.units.create') }}" wire:navigate class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-indigo-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50">Add Unit</a>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-4">
            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m21 21-4.35-4.35m1.35-5.15a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                <input type="search" wire:model.live.debounce.350ms="search" placeholder="Search units..." aria-label="Search units" class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-3 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
            </div>

            <select wire:model.live="statusFilter" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                <option value="">All statuses</option>
                <option value="{{ \App\Models\Unit::STATUS_AVAILABLE }}">Available</option>
                <option value="{{ \App\Models\Unit::STATUS_UNAVAILABLE }}">Unavailable</option>
            </select>

            <select wire:model.live="categoryFilter" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>

            <label class="inline-flex min-h-11 items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700">
                <input type="checkbox" wire:model.live="showTrashed" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/40">
                Include deleted
            </label>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-[0.14em] text-slate-500">
                    <th scope="col" class="px-4 py-3 font-medium">Unit</th>
                    <th scope="col" class="px-4 py-3 font-medium">Category</th>
                    <th scope="col" class="px-4 py-3 font-medium">Status</th>
                    <th scope="col" class="px-4 py-3 font-medium">Price</th>
                    <th scope="col" class="px-4 py-3 font-medium">Deleted</th>
                    <th scope="col" class="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($units as $unit)
                    <tr class="border-b border-slate-200 text-slate-700 transition duration-150 hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-900">{{ $unit->name }}</p>
                            <p class="text-xs text-slate-500">{{ $unit->location ?: 'No location' }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $unit->category->name }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold',
                                'border-emerald-300 bg-emerald-50 text-emerald-700' => $unit->status === \App\Models\Unit::STATUS_AVAILABLE,
                                'border-red-200 bg-red-50 text-red-700' => $unit->status !== \App\Models\Unit::STATUS_AVAILABLE,
                            ])>
                                {{ $unit->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-700">
                            @if($unit->price_display_mode === \App\Models\Unit::DISPLAY_MONTH && $unit->monthly_price_php)
                                &#8369;{{ number_format($unit->monthly_price_php) }}/month
                            @elseif($unit->nightly_price_php)
                                &#8369;{{ number_format($unit->nightly_price_php) }}/night
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $unit->deleted_at ? $unit->deleted_at->format('Y-m-d H:i') : '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap justify-end gap-2">
                                <a href="{{ route('admin.units.edit', $unit) }}" wire:navigate class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 transition hover:-translate-y-0.5 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">Edit</a>

                                @if(!$unit->trashed())
                                    @if($unit->status !== \App\Models\Unit::STATUS_AVAILABLE)
                                        <button type="button" wire:click="setAvailable({{ $unit->id }})" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 transition hover:-translate-y-0.5 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">Set Available</button>
                                    @endif
                                    @if($unit->status !== \App\Models\Unit::STATUS_UNAVAILABLE)
                                        <button type="button" wire:click="setUnavailable({{ $unit->id }})" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 transition hover:-translate-y-0.5 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">Set Unavailable</button>
                                    @endif
                                    <button type="button" wire:click="deleteUnit({{ $unit->id }})" data-confirm-title="Archive Unit" data-confirm="This will hide &quot;{{ $unit->name }}&quot; from active listings. You can restore it later." data-confirm-confirm="Archive Unit" data-confirm-cancel="Keep Unit" data-confirm-tone="danger" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-semibold text-red-700 transition hover:-translate-y-0.5 hover:bg-red-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">Delete</button>
                                @else
                                    <button type="button" wire:click="restoreUnit({{ $unit->id }})" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-600 px-3 text-xs font-semibold text-white transition hover:-translate-y-0.5 hover:bg-indigo-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">Restore</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No units found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $units->links() }}</div>
</div>
