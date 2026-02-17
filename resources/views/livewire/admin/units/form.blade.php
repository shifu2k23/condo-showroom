<div class="space-y-6 p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">{{ $isEditMode ? 'Edit Unit' : 'Create Unit' }}</h1>
        <flux:button variant="ghost" :href="route('admin.units.index')" wire:navigate>Back to Units</flux:button>
    </div>

    @if(session('status'))
        <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-300">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <flux:input wire:model="name" label="Name / Title" required />
            <flux:select wire:model="category_id" label="Category" required>
                <option value="">Select category</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </flux:select>
            <flux:input wire:model="location" label="Location" />
            <flux:select wire:model="status" label="Status" required>
                <option value="{{ \App\Models\Unit::STATUS_AVAILABLE }}">AVAILABLE</option>
                <option value="{{ \App\Models\Unit::STATUS_UNAVAILABLE }}">UNAVAILABLE</option>
            </flux:select>
            <flux:input wire:model="nightly_price_php" type="number" min="0" step="1" label="Nightly Price (PHP)" />
            <flux:input wire:model="monthly_price_php" type="number" min="0" step="1" label="Monthly Price (PHP)" />
            <flux:select wire:model="price_display_mode" label="Price Display Mode" required>
                <option value="{{ \App\Models\Unit::DISPLAY_NIGHT }}">NIGHT</option>
                <option value="{{ \App\Models\Unit::DISPLAY_MONTH }}">MONTH</option>
            </flux:select>
            <flux:select wire:model="estimator_mode" label="Estimator Mode" required>
                <option value="{{ \App\Models\Unit::ESTIMATOR_HYBRID }}">HYBRID</option>
                <option value="{{ \App\Models\Unit::ESTIMATOR_NIGHTLY_ONLY }}">NIGHTLY_ONLY</option>
                <option value="{{ \App\Models\Unit::ESTIMATOR_MONTHLY_ONLY }}">MONTHLY_ONLY</option>
            </flux:select>
        </div>

        <flux:textarea wire:model="description" label="Description" rows="5" />

        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="allow_estimator">
            Allow public estimator
        </label>

        <div class="space-y-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <h2 class="text-lg font-medium">Images</h2>
            <flux:input type="file" wire:model="newImages" multiple />
            <p class="text-xs text-zinc-500">JPG/PNG/WEBP up to 5MB each. Stored as relative paths.</p>

            @if($existingImages->isNotEmpty())
                <div class="space-y-2">
                    @foreach($existingImages as $image)
                        <div class="flex items-center justify-between gap-3 rounded-md border border-zinc-200 p-3 dark:border-zinc-700">
                            <div class="flex items-center gap-3">
                                <img src="{{ Storage::url($image->path) }}" alt="Unit image" class="h-12 w-12 rounded object-cover">
                                <div>
                                    <p class="text-sm">{{ $image->path }}</p>
                                    <p class="text-xs text-zinc-500">sort_order: {{ $image->sort_order }}</p>
                                </div>
                            </div>
                            <div class="flex gap-1">
                                <flux:button type="button" size="xs" variant="ghost" wire:click="moveImageUp({{ $image->id }})">Up</flux:button>
                                <flux:button type="button" size="xs" variant="ghost" wire:click="moveImageDown({{ $image->id }})">Down</flux:button>
                                <flux:button type="button" size="xs" variant="danger" wire:click="removeImage({{ $image->id }})" wire:confirm="Remove image?">Remove</flux:button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">{{ $isEditMode ? 'Update Unit' : 'Create Unit' }}</flux:button>
        </div>
    </form>
</div>
