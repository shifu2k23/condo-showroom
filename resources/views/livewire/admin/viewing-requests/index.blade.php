<div class="space-y-7">
    <div class="flex flex-col gap-2">
        <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Viewing Requests</h2>
        <p class="text-sm text-slate-500">Review and manage scheduled client visits.</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-4">
            <div>
                <label for="vr-status" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Status</label>
                <select id="vr-status" wire:model.live="statusFilter" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                    <option value="">All statuses</option>
                    <option value="{{ \App\Models\ViewingRequest::STATUS_PENDING }}">PENDING</option>
                    <option value="{{ \App\Models\ViewingRequest::STATUS_CONFIRMED }}">CONFIRMED</option>
                    <option value="{{ \App\Models\ViewingRequest::STATUS_CANCELLED }}">CANCELLED</option>
                </select>
            </div>

            <div>
                <label for="vr-unit" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Unit</label>
                <select id="vr-unit" wire:model.live="unitFilter" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                    <option value="">All units</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="vr-date-from" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">From</label>
                <input id="vr-date-from" type="date" wire:model.live="dateFrom" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
            </div>

            <div>
                <label for="vr-date-to" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">To</label>
                <input id="vr-date-to" type="date" wire:model.live="dateTo" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
            </div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-[0.14em] text-slate-500">
                    <th scope="col" class="px-4 py-3 font-medium">Unit</th>
                    <th scope="col" class="px-4 py-3 font-medium">Requester</th>
                    <th scope="col" class="px-4 py-3 font-medium">Scheduled</th>
                    <th scope="col" class="px-4 py-3 font-medium">Status</th>
                    <th scope="col" class="px-4 py-3 font-medium">Notes</th>
                    <th scope="col" class="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($viewingRequests as $request)
                    <tr @class([
                        'border-b border-slate-200 text-slate-700 transition duration-150 hover:bg-slate-50',
                        'bg-indigo-50/60' => $request->status === \App\Models\ViewingRequest::STATUS_PENDING,
                    ])>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $request->unit?->name ?? 'Deleted unit' }}</td>
                        <td class="px-4 py-3">
                            <p class="text-slate-900">{{ $request->requester_name }}</p>
                            <p class="text-xs text-slate-500">{{ $request->requester_email ?: '-' }} | {{ $request->requester_phone ?: '-' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p>{{ $request->requested_start_at->format('M d, Y h:i A') }}</p>
                            <p class="text-xs text-slate-500">{{ $request->requested_end_at?->format('M d, Y h:i A') ?: '-' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            @if($request->status === \App\Models\ViewingRequest::STATUS_CONFIRMED)
                                <span class="inline-flex items-center rounded-full border border-emerald-300 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">CONFIRMED</span>
                            @elseif($request->status === \App\Models\ViewingRequest::STATUS_CANCELLED)
                                <span class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">CANCELLED</span>
                            @else
                                <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">PENDING</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $request->notes ?: '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap justify-end gap-2">
                                @if($request->status === \App\Models\ViewingRequest::STATUS_PENDING)
                                    <button type="button" wire:click="confirmRequest({{ $request->id }})" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-600 px-3 text-xs font-semibold text-white transition hover:-translate-y-0.5 hover:bg-indigo-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">Confirm</button>
                                @endif
                                @if($request->status !== \App\Models\ViewingRequest::STATUS_CANCELLED)
                                    <button type="button" wire:click="cancelRequest({{ $request->id }})" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-semibold text-red-700 transition hover:-translate-y-0.5 hover:bg-red-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">Cancel</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No viewing requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $viewingRequests->links() }}</div>
</div>
