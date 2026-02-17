<div class="space-y-5 p-6">
    <h1 class="text-2xl font-semibold">Create Rental</h1>

    <form wire:submit.prevent="save" class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <flux:select wire:model="unit_id" label="Unit (optional)">
                <option value="">Select unit</option>
                @foreach($units as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                @endforeach
            </flux:select>
            @error('unit_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

            <flux:input wire:model="renter_name" label="Renter Name" required />
            @error('renter_name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

            <flux:select wire:model="id_type" label="ID Type" required>
                @foreach($idTypeOptions as $option)
                    <option value="{{ $option }}">{{ str_replace('_', ' ', $option) }}</option>
                @endforeach
            </flux:select>
            @error('id_type') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

            <flux:input wire:model="id_last4" label="ID Last 4 (optional)" maxlength="4" />
            @error('id_last4') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

            <flux:input type="datetime-local" wire:model="starts_at" label="Starts At" required />
            @error('starts_at') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

            <flux:input type="datetime-local" wire:model="ends_at" label="Ends At" required />
            @error('ends_at') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <p class="text-xs text-zinc-500">A secure rental code will be generated and shown once after save.</p>

        <div class="flex justify-end gap-2">
            <flux:button variant="ghost" :href="route('admin.rentals.index')" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">Create Rental</flux:button>
        </div>
    </form>
</div>
