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
                        <div class="aspect-video overflow-hidden bg-zinc-200 dark:bg-zinc-800">
                            <img
                                src="{{ route('tenant.media.unit-images.show', ['unitImage' => $unit->images->first()]) }}"
                                alt="{{ $unit->name }}"
                                class="h-full w-full object-cover"
                            />
                        </div>
                        @if($unit->images->count() > 1)
                            <div class="grid grid-cols-4 gap-2 p-3 sm:grid-cols-6">
                                @foreach($unit->images as $image)
                                    <img
                                        src="{{ route('tenant.media.unit-images.show', ['unitImage' => $image]) }}"
                                        alt="{{ $unit->name }}"
                                        class="aspect-square rounded-md object-cover"
                                    />
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="flex aspect-video items-center justify-center bg-zinc-200 text-zinc-400 dark:bg-zinc-800">
                            <flux:icon.photo class="h-12 w-12" />
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
