<div>
    <section class="mx-auto max-w-7xl px-4 pb-10 pt-10 sm:px-6 sm:pb-12 sm:pt-14">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Find your next stay.</h1>

        <div class="mt-8 flex flex-col gap-3 rounded-2xl border border-slate-100 bg-white p-2 shadow-xl shadow-slate-200/50 lg:flex-row lg:items-center">
            <label class="sr-only" for="showroom-search">Search by name or location</label>
            <input
                id="showroom-search"
                type="text"
                wire:model.live.debounce.350ms="search"
                placeholder="Search by name or location..."
                class="w-full flex-1 rounded-xl px-5 py-3.5 text-slate-700 outline-none ring-indigo-500/30 transition focus:ring lg:px-6 lg:py-4"
            />

            <div class="hidden h-10 w-px self-center bg-slate-200 lg:block" aria-hidden="true"></div>

            <label class="sr-only" for="showroom-category">Category</label>
            <select
                id="showroom-category"
                wire:model.live="categoryFilter"
                class="w-full cursor-pointer rounded-xl px-5 py-3.5 text-slate-600 outline-none ring-indigo-500/30 transition focus:ring lg:w-auto lg:px-6 lg:py-4"
            >
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>

            <button
                type="button"
                wire:click="$refresh"
                class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-8 py-3.5 font-semibold text-white transition-all hover:shadow-lg hover:shadow-indigo-200 lg:w-auto lg:py-4"
            >
                Search
            </button>
        </div>

        <div id="category-filters" class="scrollbar-hide mt-10 flex gap-3 overflow-x-auto pb-2" aria-label="Filters">
            <button
                type="button"
                wire:click="$set('categoryFilter', '')"
                class="rounded-full px-6 py-2 text-sm font-medium transition {{ $categoryFilter === '' ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:border-slate-400' }}"
            >
                All
            </button>
            @foreach($categories as $category)
                <button
                    type="button"
                    wire:click="$set('categoryFilter', '{{ $category->id }}')"
                    class="rounded-full px-6 py-2 text-sm font-medium transition {{ (string) $categoryFilter === (string) $category->id ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:border-slate-400' }}"
                >
                    {{ $category->name }}
                </button>
            @endforeach
        </div>
    </section>

    <section id="showroom-results" class="mx-auto max-w-7xl px-4 pb-20 sm:px-6">
        @if($units->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                <p class="text-slate-500">No units match your search.</p>
            </div>
        @else
            <div class="grid grid-cols-2 gap-x-3 gap-y-6 sm:grid-cols-2 sm:gap-8 xl:grid-cols-3">
                @foreach($units as $unit)
                    <a href="{{ route('unit.show', ['unit' => $unit->public_id]) }}" class="group block cursor-pointer">
                        <div class="relative mb-3 aspect-square overflow-hidden rounded-2xl bg-slate-200 sm:mb-4 sm:aspect-[4/5] sm:rounded-3xl">
                            @if($unit->images->isNotEmpty())
                                <img
                                    src="{{ route('tenant.media.unit-images.show', ['unitImage' => $unit->images->first()]) }}"
                                    alt="{{ $unit->name }}"
                                    class="h-full w-full object-cover transition duration-700 group-hover:scale-110"
                                    loading="lazy"
                                />
                            @else
                                <div class="flex h-full items-center justify-center text-slate-400">
                                    <flux:icon.photo class="h-12 w-12" />
                                </div>
                            @endif

                            <div class="absolute right-2 top-2 rounded-full bg-white/90 px-2.5 py-1 text-[9px] font-bold uppercase tracking-wider shadow-sm backdrop-blur sm:right-4 sm:top-4 sm:px-3 sm:text-[10px] sm:tracking-widest">
                                @if($unit->has_active_rental)
                                    Rented
                                @elseif($unit->status !== \App\Models\Unit::STATUS_AVAILABLE)
                                    Unavailable
                                @elseif($unit->has_upcoming_rental)
                                    Reserved
                                @else
                                    Available
                                @endif
                            </div>
                        </div>

                        <div class="flex items-start justify-between gap-2 sm:gap-3">
                            <div>
                                <h2 class="text-base font-bold leading-tight text-slate-900 sm:text-lg">{{ $unit->name }}</h2>
                                <p class="text-xs italic text-slate-500 sm:text-sm">{{ $unit->location ?: 'Location not provided' }}</p>
                                <p class="mt-0.5 text-[10px] uppercase tracking-wide text-slate-400 sm:mt-1 sm:text-xs">{{ $unit->category?->name ?? 'Uncategorized' }}</p>
                                @if($unit->has_active_rental && $unit->active_rental_ends_at)
                                    <p class="mt-1 hidden text-xs text-rose-600 sm:block">
                                        Rented until {{ \Carbon\Carbon::parse($unit->active_rental_ends_at)->format('M d, Y h:i A') }}
                                    </p>
                                @elseif($unit->has_upcoming_rental && $unit->next_rental_starts_at)
                                    <p class="mt-1 hidden text-xs text-amber-600 sm:block">
                                        Reserved starting {{ \Carbon\Carbon::parse($unit->next_rental_starts_at)->format('M d, Y h:i A') }}
                                    </p>
                                @endif
                            </div>

                            <div class="text-right">
                                @if($unit->price_display_mode === \App\Models\Unit::DISPLAY_MONTH && $unit->monthly_price_php)
                                    <span class="text-base font-bold text-indigo-600 sm:text-lg">&#8369;{{ number_format($unit->monthly_price_php) }}</span>
                                    <p class="text-[9px] font-bold uppercase tracking-tight text-slate-400 sm:text-[10px]">per month</p>
                                @elseif($unit->nightly_price_php)
                                    <span class="text-base font-bold text-indigo-600 sm:text-lg">&#8369;{{ number_format($unit->nightly_price_php) }}</span>
                                    <p class="text-[9px] font-bold uppercase tracking-tight text-slate-400 sm:text-[10px]">per night</p>
                                @else
                                    <p class="text-sm font-medium text-slate-400">Price unavailable</p>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-10">
                {{ $units->links() }}
            </div>
        @endif
    </section>
</div>
