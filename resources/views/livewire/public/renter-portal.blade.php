@php
    $slides = collect(glob(base_path('images/*.{jpg,jpeg,png,webp,avif,JPG,JPEG,PNG,WEBP,AVIF}'), GLOB_BRACE))
        ->map(fn (string $path) => route('project.image', ['filename' => basename($path)]))
        ->values();
    $brandName = \App\Models\AppSetting::get('site_name', config('app.name', 'Condo Showroom')) ?? config('app.name', 'Condo Showroom');
@endphp

<section class="px-4 py-10 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
            <div class="grid grid-cols-1 lg:min-h-[680px] lg:grid-cols-2">
                <div class="flex flex-col bg-white px-7 py-8 sm:px-10 sm:py-10">
                    <div class="text-base font-bold tracking-[0.22em] text-emerald-800">{{ $brandName }}</div>

                    <div class="mt-10 max-w-md">
                        <h1 class="text-4xl font-extrabold tracking-tight text-slate-900">Welcome to Renter Access</h1>
                        <p class="mt-2 text-sm text-slate-500">Enter your rental details to continue to your dashboard.</p>

                        @if($statusMessage)
                            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                                {{ $statusMessage }}
                            </div>
                        @endif

                        <form wire:submit.prevent="login" class="mt-7 space-y-4">
                            <div>
                                <label for="renter-name" class="mb-1.5 block text-sm font-semibold text-slate-700">Renter Name</label>
                                <input
                                    id="renter-name"
                                    type="text"
                                    wire:model="renter_name"
                                    required
                                    autocomplete="name"
                                    class="h-11 w-full rounded-lg border border-emerald-700/40 px-3 text-sm text-slate-900 outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-200"
                                />
                                @error('renter_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="id-type" class="mb-1.5 block text-sm font-semibold text-slate-700">ID Type</label>
                                <select
                                    id="id-type"
                                    wire:model="id_type"
                                    required
                                    class="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm text-slate-900 outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-200"
                                >
                                    @foreach($idTypeOptions as $option)
                                        <option value="{{ $option }}">{{ str_replace('_', ' ', $option) }}</option>
                                    @endforeach
                                </select>
                                @error('id_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="rental-code" class="mb-1.5 block text-sm font-semibold text-slate-700">Rental Access Code</label>
                                <input
                                    id="rental-code"
                                    type="text"
                                    wire:model="rental_code"
                                    required
                                    placeholder="123456"
                                    autocomplete="one-time-code"
                                    inputmode="numeric"
                                    class="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm tracking-[0.08em] text-slate-900 outline-none transition placeholder:tracking-normal focus:border-emerald-600 focus:ring-2 focus:ring-emerald-200"
                                />
                                @error('rental_code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <button
                                type="submit"
                                class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-emerald-700 text-sm font-semibold text-white transition hover:bg-emerald-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/50 focus-visible:ring-offset-2"
                            >
                                Open Dashboard
                            </button>
                        </form>
                    </div>

                    <div class="mt-auto pt-10 text-xs text-slate-400">
                        Terms & Conditions | Privacy Policy
                    </div>
                </div>

                <div class="relative hidden overflow-hidden bg-gradient-to-br from-emerald-700 via-emerald-600 to-emerald-800 lg:block">
                    <div class="relative z-20 flex items-center justify-between px-8 py-7 text-sm font-medium text-emerald-50/90">
                        <div class="flex items-center gap-8">
                            <span>Our Product</span>
                            <span>Store</span>
                            <span>About</span>
                        </div>
                        <button type="button" class="rounded-xl bg-white px-4 py-2 text-emerald-800 shadow-sm">Get Started</button>
                    </div>

                    @if($slides->isNotEmpty())
                        <div
                            x-data="{ current: 0, total: {{ $slides->count() }}, init() { if (this.total > 1) { setInterval(() => { this.current = (this.current + 1) % this.total; }, 2000); } } }"
                            class="relative h-[calc(100%-72px)] w-full"
                        >
                            @foreach($slides as $index => $slide)
                                <div
                                    x-show="current === {{ $index }}"
                                    x-transition:enter="transform transition ease-out duration-700"
                                    x-transition:enter-start="-translate-x-8 opacity-0"
                                    x-transition:enter-end="translate-x-0 opacity-100"
                                    x-transition:leave="transform transition ease-in duration-700 absolute inset-0"
                                    x-transition:leave-start="translate-x-0 opacity-100"
                                    x-transition:leave-end="translate-x-8 opacity-0"
                                    class="absolute inset-0"
                                >
                                    <img
                                        src="{{ $slide }}"
                                        alt="{{ $brandName }} preview {{ $index + 1 }}"
                                        class="h-full w-full object-cover object-center"
                                        loading="lazy"
                                    />
                                    <div class="absolute inset-0 bg-gradient-to-t from-emerald-950/30 via-transparent to-transparent"></div>
                                </div>
                            @endforeach

                            <div class="absolute bottom-6 left-1/2 z-30 flex -translate-x-1/2 items-center gap-2 rounded-full bg-black/20 px-3 py-1.5 backdrop-blur-sm">
                                @foreach($slides as $index => $slide)
                                    <span
                                        class="h-2 w-2 rounded-full transition"
                                        :class="current === {{ $index }} ? 'bg-white' : 'bg-white/40'"
                                    ></span>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="absolute inset-0 grid place-content-center text-center text-emerald-100">
                            <p class="text-lg font-semibold">Add slideshow images to <code class="rounded bg-emerald-900/50 px-1.5 py-0.5 text-sm">images/</code></p>
                            <p class="mt-2 text-sm text-emerald-100/80">No image files found for the right panel.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
