<div class="py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-5">
            <a href="{{ route('home') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-zinc-100">
                &larr; Back to showroom
            </a>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    @if($unit->images->isNotEmpty())
                        <div
                            x-data="{
                                current: 0,
                                total: {{ $unit->images->count() }},
                                timer: null,
                                init() {
                                    this.startAutoplay();
                                },
                                startAutoplay() {
                                    if (this.total <= 1) {
                                        return;
                                    }
                                    this.stopAutoplay();
                                    this.timer = setInterval(() => this.next(), 4500);
                                },
                                stopAutoplay() {
                                    if (this.timer) {
                                        clearInterval(this.timer);
                                        this.timer = null;
                                    }
                                },
                                next() {
                                    this.current = (this.current + 1) % this.total;
                                },
                                previous() {
                                    this.current = (this.current - 1 + this.total) % this.total;
                                },
                                goTo(index) {
                                    this.current = index;
                                },
                            }"
                            x-init="init()"
                            @mouseenter="stopAutoplay()"
                            @mouseleave="startAutoplay()"
                            class="space-y-3"
                        >
                            <div
                                class="relative aspect-video overflow-hidden bg-zinc-200 dark:bg-zinc-800"
                                role="region"
                                aria-roledescription="carousel"
                                aria-label="{{ $unit->name }} image gallery"
                                tabindex="0"
                                @keydown.arrow-right.prevent="next()"
                                @keydown.arrow-left.prevent="previous()"
                            >
                                @foreach($unit->images as $index => $image)
                                    <img
                                        x-show="current === {{ $index }}"
                                        x-transition:enter="transform transition ease-out duration-500"
                                        x-transition:enter-start="translate-x-4 opacity-0"
                                        x-transition:enter-end="translate-x-0 opacity-100"
                                        x-transition:leave="transform transition ease-in duration-500 absolute inset-0"
                                        x-transition:leave-start="translate-x-0 opacity-100"
                                        x-transition:leave-end="-translate-x-4 opacity-0"
                                        src="{{ route('tenant.media.unit-images.show', ['unitImage' => $image]) }}"
                                        alt="{{ $unit->name }} image {{ $index + 1 }}"
                                        class="absolute inset-0 h-full w-full object-cover"
                                        loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                                    />
                                @endforeach

                                <div class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-zinc-950/50 to-transparent"></div>

                                @if($unit->images->count() > 1)
                                    <div class="absolute inset-x-0 bottom-3 flex items-center justify-between px-3">
                                        <button
                                            type="button"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-black/45 text-lg font-semibold text-white transition hover:bg-black/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
                                            @click="previous(); stopAutoplay()"
                                            aria-label="Previous image"
                                        >
                                            &larr;
                                        </button>

                                        <p class="rounded-full bg-black/45 px-3 py-1 text-xs font-semibold tracking-wide text-white">
                                            <span x-text="current + 1"></span> / {{ $unit->images->count() }}
                                        </p>

                                        <button
                                            type="button"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-black/45 text-lg font-semibold text-white transition hover:bg-black/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
                                            @click="next(); stopAutoplay()"
                                            aria-label="Next image"
                                        >
                                            &rarr;
                                        </button>
                                    </div>

                                    <p class="sr-only" aria-live="polite">
                                        <span x-text="'Showing image ' + (current + 1) + ' of ' + total"></span>
                                    </p>
                                @endif
                            </div>

                            @if($unit->images->count() > 1)
                                <div class="grid grid-cols-4 gap-2 px-3 pb-3 sm:grid-cols-6">
                                    @foreach($unit->images as $index => $image)
                                        <button
                                            type="button"
                                            @click="goTo({{ $index }}); stopAutoplay()"
                                            :class="current === {{ $index }} ? 'ring-2 ring-indigo-500 ring-offset-2 ring-offset-white dark:ring-offset-zinc-900' : 'opacity-80 hover:opacity-100'"
                                            :aria-current="current === {{ $index }} ? 'true' : 'false'"
                                            aria-label="Show image {{ $index + 1 }}"
                                            class="overflow-hidden rounded-md transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/70"
                                        >
                                            <img
                                                src="{{ route('tenant.media.unit-images.show', ['unitImage' => $image]) }}"
                                                alt="{{ $unit->name }} thumbnail {{ $index + 1 }}"
                                                class="aspect-square w-full object-cover"
                                                loading="lazy"
                                            />
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="flex aspect-video flex-col items-center justify-center gap-2 bg-zinc-200 text-zinc-400 dark:bg-zinc-800">
                            <flux:icon.photo class="h-12 w-12" />
                            <p class="text-sm font-medium">No images uploaded yet.</p>
                        </div>
                    @endif
                </section>

                <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ $unit->name }}</h1>
                            <p class="text-zinc-500 dark:text-zinc-400">{{ $unit->location ?: 'Location not provided' }}</p>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Category: {{ $unit->category->name }}</p>
                        </div>
                        <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold {{ $unit->status === \App\Models\Unit::STATUS_AVAILABLE ? 'bg-green-100 text-green-700' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' }}">
                            {{ $unit->status === \App\Models\Unit::STATUS_AVAILABLE ? 'Available' : 'Unavailable' }}
                        </span>
                    </div>

                    <div class="prose max-w-none text-zinc-700 dark:prose-invert dark:text-zinc-300">
                        <p class="whitespace-pre-line">{{ $unit->description ?: 'No description available.' }}</p>
                    </div>
                </section>

                <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="mb-3 text-lg font-semibold text-zinc-900 dark:text-zinc-100">Location</h2>

                    @if($unit->hasLocation())
                        <div
                            data-leaflet-readonly
                            data-lat="{{ $unit->latitude }}"
                            data-lng="{{ $unit->longitude }}"
                            class="h-72 w-full overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800"
                        ></div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <a
                                href="{{ $unit->googleMapsUrl() }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex min-h-10 items-center justify-center rounded-lg border border-zinc-300 bg-white px-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                            >
                                Open in Google Maps
                            </a>
                            <a
                                href="{{ $unit->googleDirectionsUrl() }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex min-h-10 items-center justify-center rounded-lg bg-zinc-900 px-3 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-zinc-200 dark:text-zinc-900 dark:hover:bg-zinc-300"
                            >
                                Get Directions
                            </a>
                        </div>
                    @else
                        <p class="rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">Location not available.</p>
                    @endif
                </section>
            </div>

            <div class="space-y-6">
                <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="mb-3 text-lg font-semibold text-zinc-900 dark:text-zinc-100">Pricing</h2>
                    <div class="mb-4 border-b border-zinc-200 pb-4 dark:border-zinc-700">
                        @if($unit->price_display_mode === \App\Models\Unit::DISPLAY_MONTH && $unit->monthly_price_php)
                            <p class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">&#8369;{{ number_format($unit->monthly_price_php) }}</p>
                            <p class="text-sm text-zinc-500">per month</p>
                        @elseif($unit->nightly_price_php)
                            <p class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">&#8369;{{ number_format($unit->nightly_price_php) }}</p>
                            <p class="text-sm text-zinc-500">per night</p>
                        @else
                            <p class="text-sm text-zinc-500">No default price configured.</p>
                        @endif
                    </div>

                    @if($unit->allow_estimator)
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Price Estimator</h3>
                        <div class="space-y-3">
                            <flux:input type="date" wire:model.live="checkIn" label="Check-in" />
                            <flux:input type="date" wire:model.live="checkOut" label="Check-out" />

                            @if($estimateError)
                                <p class="rounded-md bg-red-50 p-2 text-sm text-red-600 dark:bg-red-900/20 dark:text-red-300">{{ $estimateError }}</p>
                            @endif

                            @if($estimatedPrice !== null)
                                <div class="rounded-md bg-zinc-100 p-3 dark:bg-zinc-800">
                                    <p class="text-sm text-zinc-600 dark:text-zinc-300">Estimated total</p>
                                    <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100">&#8369;{{ number_format($estimatedPrice) }}</p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $estimateBreakdown }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </section>

                <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="mb-3 text-lg font-semibold text-zinc-900 dark:text-zinc-100">Request a Viewing</h2>

                    @if($requestSuccess)
                        <div class="rounded-md bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-300">
                            Viewing request submitted successfully.
                        </div>
                        <flux:button class="mt-3" variant="ghost" wire:click="$set('requestSuccess', false)">Submit another request</flux:button>
                    @else
                        <form wire:submit.prevent="submitRequest" class="space-y-3">
                            <input
                                type="text"
                                wire:model="website"
                                class="hidden"
                                tabindex="-1"
                                autocomplete="off"
                                aria-hidden="true"
                            />

                            <flux:input type="date" wire:model="requestDate" label="Preferred Date" required min="{{ date('Y-m-d') }}" />
                            @error('requestDate') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            @error('requested_start_at') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                            <flux:select wire:model="requestTime" label="Preferred Time" required>
                                <option value="">Select a time</option>
                                @foreach($timeSlots as $slot)
                                    <option value="{{ $slot }}">{{ \Carbon\Carbon::createFromFormat('H:i', $slot)->format('h:i A') }}</option>
                                @endforeach
                            </flux:select>
                            @error('requestTime') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            @error('requested_end_at') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                            <flux:input type="text" wire:model="clientName" label="Your Name" required />
                            @error('clientName') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                            <flux:input type="email" wire:model="clientEmail" label="Email (optional)" />
                            @error('clientEmail') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                            <flux:input type="text" wire:model="clientPhone" label="Phone (optional)" />
                            @error('clientPhone') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                            <flux:textarea wire:model="clientNotes" label="Notes (optional)" rows="3" />
                            @error('clientNotes') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                            <flux:button type="submit" variant="primary" class="w-full justify-center">Submit Request</flux:button>
                        </form>
                    @endif
                </section>
            </div>
        </div>
    </div>
</div>
