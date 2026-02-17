<div class="py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="mb-4 text-3xl font-bold text-zinc-900 dark:text-zinc-100">Condo Showroom</h1>

            <div class="mb-5 flex flex-col gap-3 md:flex-row">
                <flux:input
                    wire:model.live.debounce.350ms="search"
                    class="w-full"
                    icon="magnifying-glass"
                    placeholder="Search by name or location"
                />

                <flux:select wire:model.live="categoryFilter" class="md:w-72">
                    <option value="">All categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="$set('categoryFilter', '')"
                    class="rounded-full px-4 py-1.5 text-sm font-medium {{ $categoryFilter === '' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' }}"
                >
                    All
                </button>
                @foreach($categories as $category)
                    <button
                        type="button"
                        wire:click="$set('categoryFilter', '{{ $category->id }}')"
                        class="rounded-full px-4 py-1.5 text-sm font-medium {{ (string) $categoryFilter === (string) $category->id ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' }}"
                    >
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>
        </div>

        @if($units->isEmpty())
            <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 px-6 py-14 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-zinc-600 dark:text-zinc-400">No units match your search.</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($units as $unit)
                    <a
                        href="{{ route('unit.show', $unit) }}"
                        class="overflow-hidden rounded-2xl border border-zinc-200 bg-white transition-shadow hover:shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
                    >
                        <div class="relative aspect-[4/3] overflow-hidden bg-zinc-200 dark:bg-zinc-800">
                            @if($unit->images->isNotEmpty())
                                <img
                                    src="{{ Storage::url($unit->images->first()->path) }}"
                                    alt="{{ $unit->name }}"
                                    class="h-full w-full object-cover"
                                />
                            @else
                                <div class="flex h-full items-center justify-center text-zinc-400">
                                    <flux:icon.photo class="h-10 w-10" />
                                </div>
                            @endif

                            <div class="absolute right-3 top-3 rounded-full px-3 py-1 text-xs font-semibold {{ $unit->status === \App\Models\Unit::STATUS_AVAILABLE ? 'bg-green-100 text-green-700' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' }}">
                                {{ $unit->status === \App\Models\Unit::STATUS_AVAILABLE ? 'Available' : 'Unavailable' }}
                            </div>
                        </div>

                        <div class="p-4">
                            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $unit->name }}</h2>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $unit->location ?: 'Location not provided' }}</p>

                            <div class="mt-4 flex items-end justify-between">
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-zinc-500">Category</p>
                                    <p class="font-medium text-zinc-800 dark:text-zinc-200">{{ $unit->category->name }}</p>
                                </div>

                                <div class="text-right">
                                    @if($unit->price_display_mode === \App\Models\Unit::DISPLAY_MONTH && $unit->monthly_price_php)
                                        <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100">&#8369;{{ number_format($unit->monthly_price_php) }}</p>
                                        <p class="text-xs text-zinc-500">per month</p>
                                    @elseif($unit->nightly_price_php)
                                        <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100">&#8369;{{ number_format($unit->nightly_price_php) }}</p>
                                        <p class="text-xs text-zinc-500">per night</p>
                                    @else
                                        <p class="text-sm font-medium text-zinc-500">Price unavailable</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $units->links() }}
            </div>
        @endif
    </div>
</div>
