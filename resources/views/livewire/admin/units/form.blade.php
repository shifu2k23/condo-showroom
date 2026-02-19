<div class="space-y-7">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $isEditMode ? 'Edit Unit' : 'Create Unit' }}</h2>
        <a href="{{ route('admin.units.index') }}" wire:navigate class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:-translate-y-0.5 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50">Back to Units</a>
    </div>

    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        @if(! $isEditMode)
            <div class="rounded-2xl border border-indigo-200 bg-indigo-50/60 p-4 sm:p-5">
                <label for="unit-location-preset" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-indigo-700">Condo Location Preset (Davao City)</label>
                <select id="unit-location-preset" wire:model.live="selectedLocationPreset" class="h-11 w-full rounded-xl border border-indigo-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                    <option value="">Select condo / tower</option>
                    @foreach($davaoCondoLocationPresets as $presetKey => $preset)
                        <option value="{{ $presetKey }}">{{ $preset['name'] }} ({{ $preset['latitude'] }}, {{ $preset['longitude'] }})</option>
                    @endforeach
                </select>
                <p class="mt-2 text-xs text-indigo-700/90">Pag pumili ka ng condo/tower, auto-fill ang name, location, at lat/long pin sa map.</p>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label for="unit-name" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Name / Title</label>
                <input id="unit-name" type="text" wire:model="name" required class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="unit-category" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Category</label>
                <select id="unit-category" wire:model="category_id" required class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                    <option value="">Select category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="unit-location" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Location</label>
                <input id="unit-location" type="text" wire:model="location" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                @error('location') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="unit-status" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Status</label>
                <select id="unit-status" wire:model="status" required class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                    <option value="{{ \App\Models\Unit::STATUS_AVAILABLE }}">AVAILABLE</option>
                    <option value="{{ \App\Models\Unit::STATUS_UNAVAILABLE }}">UNAVAILABLE</option>
                </select>
                @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="unit-nightly" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Nightly Price (PHP)</label>
                <input id="unit-nightly" type="number" min="0" step="1" wire:model="nightly_price_php" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                @error('nightly_price_php') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="unit-monthly" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Monthly Price (PHP)</label>
                <input id="unit-monthly" type="number" min="0" step="1" wire:model="monthly_price_php" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                @error('monthly_price_php') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="unit-display-mode" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Price Display Mode</label>
                <select id="unit-display-mode" wire:model="price_display_mode" required class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                    <option value="{{ \App\Models\Unit::DISPLAY_NIGHT }}">NIGHT</option>
                    <option value="{{ \App\Models\Unit::DISPLAY_MONTH }}">MONTH</option>
                </select>
                @error('price_display_mode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="unit-estimator-mode" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Estimator Mode</label>
                <select id="unit-estimator-mode" wire:model="estimator_mode" required class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                    <option value="{{ \App\Models\Unit::ESTIMATOR_HYBRID }}">HYBRID</option>
                    <option value="{{ \App\Models\Unit::ESTIMATOR_NIGHTLY_ONLY }}">NIGHTLY_ONLY</option>
                    <option value="{{ \App\Models\Unit::ESTIMATOR_MONTHLY_ONLY }}">MONTHLY_ONLY</option>
                </select>
                @error('estimator_mode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div
            class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5"
            data-leaflet-picker
            data-livewire-id="{{ $this->getId() }}"
            data-lat="{{ $latitude ?? '' }}"
            data-lng="{{ $longitude ?? '' }}"
        >
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Location Pin</h3>
                <p class="text-xs text-slate-500">Click on the map to drop a pin, or type latitude/longitude manually.</p>
            </div>

            <div wire:ignore>
                <div data-leaflet-picker-map class="h-[360px] w-full overflow-hidden rounded-xl border border-slate-300 bg-white"></div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    data-leaflet-action="geolocate"
                    class="inline-flex min-h-10 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 text-xs font-semibold text-indigo-700 transition hover:-translate-y-0.5 hover:bg-indigo-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400/40 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50"
                >
                    Use my current location
                </button>
                <button
                    type="button"
                    data-leaflet-action="clear"
                    class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-medium text-slate-700 transition hover:-translate-y-0.5 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400/40 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50"
                >
                    Clear pin
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="unit-latitude" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Latitude</label>
                    <input id="unit-latitude" type="text" wire:model="latitude" data-leaflet-lat-display inputmode="decimal" placeholder="e.g. 14.5995000" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 font-mono text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                    @error('latitude') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="unit-longitude" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Longitude</label>
                    <input id="unit-longitude" type="text" wire:model="longitude" data-leaflet-lng-display inputmode="decimal" placeholder="e.g. 120.9842000" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 font-mono text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                    @error('longitude') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="unit-address-text" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Address Text (optional)</label>
                <input id="unit-address-text" type="text" wire:model="address_text" placeholder="Nearest landmark or address note" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                @error('address_text') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="unit-description" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Description</label>
            <textarea id="unit-description" wire:model="description" rows="5" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"></textarea>
            @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        @if($isEditMode)
            <div class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">AI Description</h3>
                        <p class="text-xs text-slate-500">Admin-only. Generate a preview draft, then apply it to Description manually.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label for="ai-tone" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Tone</label>
                        <select id="ai-tone" wire:model="aiTone" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                            @foreach($aiToneOptions as $toneOption)
                                <option value="{{ $toneOption }}">{{ $toneOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="ai-length" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Length</label>
                        <select id="ai-length" wire:model="aiLength" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                            @foreach($aiLengthOptions as $lengthOption)
                                <option value="{{ $lengthOption }}">{{ $lengthOption }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="generateAiDescription" wire:loading.attr="disabled" wire:target="generateAiDescription" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-600 px-4 text-xs font-semibold text-white transition hover:-translate-y-0.5 hover:bg-indigo-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50 disabled:cursor-not-allowed disabled:opacity-70">
                        <span wire:loading.remove wire:target="generateAiDescription">{{ $ai_description_draft ? 'Regenerate' : 'Generate AI Description' }}</span>
                        <span wire:loading wire:target="generateAiDescription">Generating...</span>
                    </button>
                    <button type="button" wire:click="applyAiDescriptionDraft" @disabled(blank($ai_description_draft)) class="inline-flex min-h-10 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 px-4 text-xs font-semibold text-emerald-700 transition hover:-translate-y-0.5 hover:bg-emerald-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/40 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50 disabled:cursor-not-allowed disabled:opacity-60">
                        Apply to Description
                    </button>
                    <button type="button" wire:click="clearAiDescriptionDraft" @disabled(blank($ai_description_draft)) class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-xs font-medium text-slate-700 transition hover:-translate-y-0.5 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400/40 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50 disabled:cursor-not-allowed disabled:opacity-60">
                        Clear Draft
                    </button>
                </div>

                @error('ai_generation')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror

                @if($aiWarnings !== [])
                    <ul class="space-y-1 text-xs text-amber-700">
                        @foreach($aiWarnings as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                @endif

                <div>
                    <label for="ai-draft-preview" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Draft Preview (Read-only)</label>
                    <textarea id="ai-draft-preview" rows="8" readonly class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:outline-none">{{ $ai_description_draft ?? '' }}</textarea>
                </div>
            </div>
        @endif

        <label class="inline-flex min-h-11 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
            <input type="checkbox" wire:model="allow_estimator" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/40">
            Allow public estimator
        </label>

        <div class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <h3 class="text-lg font-semibold text-slate-900">Images</h3>
            <input type="file" wire:model="newImages" multiple class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-600 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
            <p class="text-xs text-slate-500">JPG/PNG/WEBP up to 5MB each. Stored as relative paths.</p>
            @error('newImages.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

            @if($existingImages->isNotEmpty())
                <div class="space-y-2">
                    @foreach($existingImages as $image)
                        <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <img src="{{ Storage::url($image->path) }}" alt="Unit image" class="h-14 w-14 rounded-lg object-cover">
                                <div>
                                    <p class="text-sm text-slate-700">{{ $image->path }}</p>
                                    <p class="text-xs text-slate-500">sort_order: {{ $image->sort_order }}</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" wire:click="moveImageUp({{ $image->id }})" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 transition hover:-translate-y-0.5 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">Up</button>
                                <button type="button" wire:click="moveImageDown({{ $image->id }})" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 transition hover:-translate-y-0.5 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">Down</button>
                                <button type="button" wire:click="removeImage({{ $image->id }})" data-confirm-title="Remove Image" data-confirm="Remove this image from the unit gallery?" data-confirm-confirm="Remove Image" data-confirm-cancel="Keep Image" data-confirm-tone="danger" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-semibold text-red-700 transition hover:-translate-y-0.5 hover:bg-red-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">Remove</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex justify-end">
            <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-indigo-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">{{ $isEditMode ? 'Update Unit' : 'Create Unit' }}</button>
        </div>
    </form>
</div>
