<div>
    <section class="mx-auto max-w-7xl px-4 pb-10 pt-10 sm:px-6 sm:pb-12 sm:pt-14">
        <div class="relative overflow-hidden rounded-[2rem] border border-slate-200/70 bg-white/80 px-4 py-5 shadow-[0_24px_90px_-36px_rgba(15,23,42,0.4)] backdrop-blur sm:px-8 sm:py-8">
            <div aria-hidden="true" class="pointer-events-none absolute -left-10 top-0 h-36 w-36 rounded-full bg-teal-300/35 blur-3xl"></div>
            <div aria-hidden="true" class="pointer-events-none absolute right-0 top-0 h-40 w-40 rounded-full bg-amber-300/30 blur-3xl"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -bottom-16 left-1/3 h-40 w-40 rounded-full bg-pink-300/25 blur-3xl"></div>

            <div class="showroom-stage-heading relative">
                <p class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-600 shadow-sm">
                    <span class="h-2 w-2 rounded-full bg-teal-500"></span>
                    Featured Stays
                </p>
                <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Find your next stay.</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600 sm:text-base">
                    Browse curated units, filter by category, and open each listing with complete pricing and availability details.
                </p>
            </div>

            <div class="showroom-stage-controls mt-7 flex flex-col gap-3 rounded-2xl border border-slate-100 bg-white p-2 shadow-xl shadow-slate-200/50 lg:flex-row lg:items-center">
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
                    class="inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-8 py-3.5 font-semibold text-white transition-all hover:shadow-lg hover:shadow-slate-300 lg:w-auto lg:py-4"
                >
                    Search
                </button>
            </div>

            <div id="category-filters" class="showroom-stage-filters scrollbar-hide mt-7 flex gap-3 overflow-x-auto pb-2" aria-label="Filters">
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
                    @php
                        $statusLabel = 'Available';
                        $statusBadgeClasses = 'border-emerald-200 bg-emerald-50 text-emerald-800';
                        $statusTextClasses = 'text-emerald-700 dark:text-emerald-400';

                        if ($unit->has_active_rental) {
                            $statusLabel = 'Rented';
                            $statusBadgeClasses = 'border-rose-200 bg-rose-50 text-rose-800';
                            $statusTextClasses = 'text-rose-600 dark:text-rose-400';
                        } elseif ($unit->status !== \App\Models\Unit::STATUS_AVAILABLE) {
                            $statusLabel = 'Unavailable';
                            $statusBadgeClasses = 'border-slate-300 bg-slate-100 text-slate-700';
                            $statusTextClasses = 'text-slate-600 dark:text-slate-300';
                        } elseif ($unit->has_upcoming_rental) {
                            $statusLabel = 'Reserved';
                            $statusBadgeClasses = 'border-amber-200 bg-amber-50 text-amber-800';
                            $statusTextClasses = 'text-amber-700 dark:text-amber-400';
                        }
                    @endphp
                    <a
                        href="{{ route('unit.show', ['unit' => $unit->public_id]) }}"
                        class="showroom-card-reveal group block cursor-pointer"
                        style="--reveal-index: {{ $loop->index }};"
                    >
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

                            <div @class([
                                'absolute right-2 top-2 rounded-full border px-2.5 py-1 text-[9px] font-bold uppercase tracking-wider shadow-sm backdrop-blur sm:right-4 sm:top-4 sm:px-3 sm:text-[10px] sm:tracking-widest',
                                $statusBadgeClasses,
                            ])>
                                {{ $statusLabel }}
                            </div>

                            <div class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/85 via-slate-900/35 to-transparent px-3 py-3 sm:px-4 sm:py-4">
                                <p class="truncate text-xs font-semibold uppercase tracking-wide text-slate-100 sm:text-sm">{{ $unit->category?->name ?? 'Uncategorized' }}</p>
                                <p class="truncate text-[11px] text-slate-200 sm:text-xs">{{ $unit->location ?: 'Location not provided' }}</p>
                            </div>
                        </div>

                        <div class="flex items-start justify-between gap-2 rounded-2xl border border-slate-200/70 bg-white/90 p-2.5 shadow-sm sm:gap-3 sm:p-3 dark:border-slate-700/80 dark:bg-slate-900/80">
                            <div>
                                <h2 class="text-lg font-extrabold leading-tight tracking-tight text-slate-900 sm:text-xl dark:text-slate-100">{{ $unit->name }}</h2>
                                <p class="text-sm italic text-slate-600 sm:text-base dark:text-slate-300">{{ $unit->location ?: 'Location not provided' }}</p>
                                <p class="mt-0.5 text-xs uppercase tracking-wide text-slate-500 sm:mt-1 dark:text-slate-400">{{ $unit->category?->name ?? 'Uncategorized' }}</p>
                                <p @class([
                                    'mt-1 text-xs font-semibold',
                                    $statusTextClasses,
                                ])>
                                    {{ $statusLabel }}
                                </p>
                                @if($unit->has_active_rental && $unit->active_rental_ends_at)
                                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                        Rented until {{ \Carbon\Carbon::parse($unit->active_rental_ends_at)->format('M d, Y h:i A') }}
                                    </p>
                                @elseif($unit->has_upcoming_rental && $unit->next_rental_starts_at)
                                    <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                        Reserved starting {{ \Carbon\Carbon::parse($unit->next_rental_starts_at)->format('M d, Y h:i A') }}
                                    </p>
                                @endif
                            </div>

                            <div class="text-right">
                                @if($unit->price_display_mode === \App\Models\Unit::DISPLAY_MONTH && $unit->monthly_price_php)
                                    <span class="text-lg font-bold text-indigo-700 sm:text-xl dark:text-indigo-300">&#8369;{{ number_format($unit->monthly_price_php) }}</span>
                                    <p class="text-[10px] font-bold uppercase tracking-tight text-slate-500 sm:text-xs dark:text-slate-300">per month</p>
                                @elseif($unit->nightly_price_php)
                                    <span class="text-lg font-bold text-indigo-700 sm:text-xl dark:text-indigo-300">&#8369;{{ number_format($unit->nightly_price_php) }}</span>
                                    <p class="text-[10px] font-bold uppercase tracking-tight text-slate-500 sm:text-xs dark:text-slate-300">per night</p>
                                @else
                                    <p class="text-sm font-medium text-slate-500 dark:text-slate-300">Price unavailable</p>
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
