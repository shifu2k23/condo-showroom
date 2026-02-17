<div class="space-y-5 p-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <h1 class="text-2xl font-semibold">Rentals</h1>
        <flux:button :href="route('admin.rentals.create')" wire:navigate>Add Rental</flux:button>
    </div>

    @if ($issuedRentalCode)
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-900 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-100">
            <p class="text-sm font-semibold">Renter Access Code (shown once)</p>
            <p class="mt-1 font-mono text-2xl tracking-wider">{{ $issuedRentalCode }}</p>
            <p class="mt-2 text-xs">Print or hand this to the renter now. It cannot be recovered later.</p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <flux:input wire:model.live.debounce.350ms="search" icon="magnifying-glass" placeholder="Search renter name..." />
        <flux:select wire:model.live="statusFilter">
            <option value="">All statuses</option>
            <option value="{{ \App\Models\Rental::STATUS_ACTIVE }}">Active</option>
            <option value="{{ \App\Models\Rental::STATUS_CANCELLED }}">Cancelled</option>
        </flux:select>
        <flux:select wire:model.live="unitFilter">
            <option value="">All units</option>
            @foreach($units as $unit)
                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
            @endforeach
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">Renter</th>
                    <th class="px-4 py-3 text-left font-medium">Unit</th>
                    <th class="px-4 py-3 text-left font-medium">ID</th>
                    <th class="px-4 py-3 text-left font-medium">Code Last4</th>
                    <th class="px-4 py-3 text-left font-medium">Window</th>
                    <th class="px-4 py-3 text-left font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($rentals as $rental)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $rental->renter_name }}</td>
                        <td class="px-4 py-3">{{ $rental->unit?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $rental->id_type }} @if($rental->id_last4) ({{ $rental->id_last4 }}) @endif</td>
                        <td class="px-4 py-3 font-mono">{{ $rental->public_code_last4 ?? '-' }}</td>
                        <td class="px-4 py-3 text-xs">
                            <div>{{ $rental->starts_at?->format('Y-m-d H:i') }}</div>
                            <div class="text-zinc-500">to {{ $rental->ends_at?->format('Y-m-d H:i') }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $rental->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-zinc-500">No rentals found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $rentals->links() }}</div>
</div>
