<div>
    <section class="mx-auto max-w-7xl px-6 pb-12 pt-14">
        <h1 class="text-4xl font-extrabold tracking-tight text-slate-900">Find your next stay.</h1>

        <div class="mt-8 flex flex-col gap-4 rounded-2xl border border-slate-100 bg-white p-2 shadow-xl shadow-slate-200/50 md:flex-row">
            <label class="sr-only" for="showroom-search">Search by name or location</label>
            <input
                id="showroom-search"
                type="text"
                wire:model.live.debounce.350ms="search"
                placeholder="Search by name or location..."
                class="flex-1 rounded-xl px-6 py-4 text-slate-700 outline-none ring-indigo-500/30 transition focus:ring"
            />

            <div class="hidden h-10 w-px self-center bg-slate-200 md:block" aria-hidden="true"></div>

            <label class="sr-only" for="showroom-category">Category</label>
            <select
                id="showroom-category"
                wire:model.live="categoryFilter"
                class="cursor-pointer rounded-xl px-6 py-4 text-slate-600 outline-none ring-indigo-500/30 transition focus:ring"
            >
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>

            <button
                type="button"
                wire:click="$refresh"
                class="rounded-xl bg-indigo-600 px-8 py-4 font-semibold text-white transition-all hover:shadow-lg hover:shadow-indigo-200"
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

    <section id="showroom-results" class="mx-auto max-w-7xl px-6 pb-20">
        @if($units->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                <p class="text-slate-500">No units match your search.</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-10 md:grid-cols-3">
                @foreach($units as $unit)
                    <a href="{{ route('unit.show', $unit) }}" class="group block cursor-pointer">
                        <div class="relative mb-4 aspect-[4/5] overflow-hidden rounded-3xl bg-slate-200">
                            @if($unit->images->isNotEmpty())
                                <img
                                    src="{{ Storage::url($unit->images->first()->path) }}"
                                    alt="{{ $unit->name }}"
                                    class="h-full w-full object-cover transition duration-700 group-hover:scale-110"
                                    loading="lazy"
                                />
                            @else
                                <div class="flex h-full items-center justify-center text-slate-400">
                                    <flux:icon.photo class="h-12 w-12" />
                                </div>
                            @endif

                            <div class="absolute right-4 top-4 rounded-full bg-white/90 px-3 py-1 text-[10px] font-bold uppercase tracking-widest shadow-sm backdrop-blur">
                                {{ $unit->status === \App\Models\Unit::STATUS_AVAILABLE ? 'Available' : 'Unavailable' }}
                            </div>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">{{ $unit->name }}</h2>
                                <p class="text-sm italic text-slate-500">{{ $unit->location ?: 'Location not provided' }}</p>
                                <p class="mt-1 text-xs uppercase tracking-wide text-slate-400">{{ $unit->category?->name ?? 'Uncategorized' }}</p>
                            </div>

                            <div class="text-right">
                                @if($unit->price_display_mode === \App\Models\Unit::DISPLAY_MONTH && $unit->monthly_price_php)
                                    <span class="text-lg font-bold text-indigo-600">&#8369;{{ number_format($unit->monthly_price_php) }}</span>
                                    <p class="text-[10px] font-bold uppercase tracking-tight text-slate-400">per month</p>
                                @elseif($unit->nightly_price_php)
                                    <span class="text-lg font-bold text-indigo-600">&#8369;{{ number_format($unit->nightly_price_php) }}</span>
                                    <p class="text-[10px] font-bold uppercase tracking-tight text-slate-400">per night</p>
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
