<div class="space-y-5 p-6">
    <h1 class="text-2xl font-semibold">Viewing Requests</h1>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
        <flux:select wire:model.live="statusFilter">
            <option value="">All statuses</option>
            <option value="{{ \App\Models\ViewingRequest::STATUS_PENDING }}">PENDING</option>
            <option value="{{ \App\Models\ViewingRequest::STATUS_CONFIRMED }}">CONFIRMED</option>
            <option value="{{ \App\Models\ViewingRequest::STATUS_CANCELLED }}">CANCELLED</option>
        </flux:select>
        <flux:select wire:model.live="unitFilter">
            <option value="">All units</option>
            @foreach($units as $unit)
                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
            @endforeach
        </flux:select>
        <flux:input type="date" wire:model.live="dateFrom" label="From" />
        <flux:input type="date" wire:model.live="dateTo" label="To" />
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">Unit</th>
                    <th class="px-4 py-3 text-left font-medium">Requester</th>
                    <th class="px-4 py-3 text-left font-medium">Scheduled</th>
                    <th class="px-4 py-3 text-left font-medium">Status</th>
                    <th class="px-4 py-3 text-left font-medium">Notes</th>
                    <th class="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($viewingRequests as $request)
                    <tr class="{{ $request->status === \App\Models\ViewingRequest::STATUS_PENDING ? 'bg-amber-50/40 dark:bg-amber-900/10' : '' }}">
                        <td class="px-4 py-3">{{ $request->unit?->name ?? 'Deleted unit' }}</td>
                        <td class="px-4 py-3">
                            <p>{{ $request->requester_name }}</p>
                            <p class="text-xs text-zinc-500">{{ $request->requester_email ?: '-' }} • {{ $request->requester_phone ?: '-' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p>{{ $request->requested_start_at->format('M d, Y h:i A') }}</p>
                            <p class="text-xs text-zinc-500">{{ $request->requested_end_at?->format('M d, Y h:i A') ?: '-' }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $request->status }}</td>
                        <td class="px-4 py-3 text-zinc-500">{{ $request->notes ?: '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                @if($request->status === \App\Models\ViewingRequest::STATUS_PENDING)
                                    <flux:button size="xs" variant="primary" wire:click="confirmRequest({{ $request->id }})">Confirm</flux:button>
                                @endif
                                @if($request->status !== \App\Models\ViewingRequest::STATUS_CANCELLED)
                                    <flux:button size="xs" variant="danger" wire:click="cancelRequest({{ $request->id }})">Cancel</flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-zinc-500">No viewing requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $viewingRequests->links() }}</div>
</div>
