<div class="space-y-7">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Rentals</h2>
        <a href="{{ route('admin.rentals.create') }}" wire:navigate class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-indigo-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50">Add Rental</a>
    </div>

    @if ($issuedRentalCode)
        <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 text-indigo-900">
            <p class="text-sm font-semibold uppercase tracking-[0.14em]">Renter Access Code (shown once)</p>
            <p class="mt-2 break-all font-mono text-xl tracking-[0.14em] sm:text-3xl sm:tracking-[0.22em]">{{ $issuedRentalCode }}</p>
            <p class="mt-2 text-sm text-indigo-700">Print or hand this to the renter now. It cannot be recovered later.</p>
        </div>
    @endif

    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m21 21-4.35-4.35m1.35-5.15a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                <input type="search" wire:model.live.debounce.350ms="search" placeholder="Search renter name..." aria-label="Search renter" class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-3 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
            </div>

            <select wire:model.live="statusFilter" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                <option value="">All statuses</option>
                <option value="{{ \App\Models\Rental::STATUS_ACTIVE }}">Active</option>
                <option value="{{ \App\Models\Rental::STATUS_CANCELLED }}">Cancelled</option>
            </select>

            <select wire:model.live="unitFilter" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                <option value="">All units</option>
                @foreach($units as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-[0.14em] text-slate-500">
                    <th scope="col" class="px-4 py-3 font-medium">Renter</th>
                    <th scope="col" class="px-4 py-3 font-medium">Contact</th>
                    <th scope="col" class="px-4 py-3 font-medium">Unit</th>
                    <th scope="col" class="px-4 py-3 font-medium">ID</th>
                    <th scope="col" class="px-4 py-3 font-medium">Code Last4</th>
                    <th scope="col" class="px-4 py-3 font-medium">Window</th>
                    <th scope="col" class="px-4 py-3 font-medium">Status</th>
                    <th scope="col" class="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rentals as $rental)
                    <tr class="border-b border-slate-200 text-slate-700 transition duration-150 hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $rental->renter_name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $rental->contact_number ?: '-' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $rental->unit?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $rental->id_type }} @if($rental->id_last4) ({{ $rental->id_last4 }}) @endif</td>
                        <td class="px-4 py-3 font-mono text-slate-700">{{ $rental->public_code_last4 ?? '-' }}</td>
                        <td class="px-4 py-3 text-xs">
                            <div>{{ $rental->starts_at?->format('Y-m-d H:i') }}</div>
                            <div class="text-slate-500">to {{ $rental->ends_at?->format('Y-m-d H:i') }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($rental->status === \App\Models\Rental::STATUS_ACTIVE)
                                <span class="inline-flex items-center rounded-full border border-emerald-300 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">ACTIVE</span>
                            @else
                                <span class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">CANCELLED</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap justify-end gap-2">
                                <a href="{{ route('admin.rentals.edit', $rental) }}" wire:navigate class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 transition hover:-translate-y-0.5 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">Edit</a>
                                <button type="button" wire:click="deleteRental({{ $rental->id }})" data-confirm-title="Delete Rental Record" data-confirm="You are about to delete renter &quot;{{ $rental->renter_name }}&quot; and its access timeline." data-confirm-confirm="Delete Rental" data-confirm-cancel="Keep Record" data-confirm-tone="danger" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-semibold text-red-700 transition hover:-translate-y-0.5 hover:bg-red-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">Delete</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">No rentals found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $rentals->links() }}</div>
</div>
