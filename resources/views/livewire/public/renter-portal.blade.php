<div class="py-10">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Renter Portal</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Enter your rental details and one-time code to continue.</p>

            @if($statusMessage)
                <div class="mt-4 rounded-md bg-zinc-100 p-3 text-sm text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    {{ $statusMessage }}
                </div>
            @endif

            @if($authenticatedRental)
                <div class="mt-5 space-y-3">
                    <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">
                        Access verified for <strong>{{ $authenticatedRental->renter_name }}</strong>.
                    </div>

                    <div class="rounded-lg border border-zinc-200 p-4 text-sm dark:border-zinc-700">
                        <p><span class="font-medium">Unit:</span> {{ $authenticatedRental->unit?->name ?? 'N/A' }}</p>
                        <p><span class="font-medium">Access Ends:</span> {{ $authenticatedRental->ends_at?->format('M d, Y h:i A') }}</p>
                    </div>

                    <flux:button variant="ghost" wire:click="logout">Sign Out</flux:button>
                </div>
            @else
                <form wire:submit.prevent="login" class="mt-5 space-y-3">
                    <flux:input wire:model="renter_name" label="Renter Name" required />
                    @error('renter_name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                    <flux:select wire:model="id_type" label="ID Type" required>
                        @foreach($idTypeOptions as $option)
                            <option value="{{ $option }}">{{ str_replace('_', ' ', $option) }}</option>
                        @endforeach
                    </flux:select>
                    @error('id_type') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                    <flux:input wire:model="rental_code" label="Rental Access Code" placeholder="XXXX-XXXX-XXXX" required />
                    @error('rental_code') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                    <flux:button type="submit" variant="primary" class="w-full justify-center">Access Rental</flux:button>
                </form>
            @endif
        </div>
    </div>
</div>
