<div class="space-y-7">
    <section class="relative overflow-hidden rounded-3xl border border-indigo-200 bg-gradient-to-br from-indigo-50 via-white to-cyan-50 p-5 shadow-sm sm:p-6">
        <div class="absolute -right-8 -top-10 h-32 w-32 rounded-full bg-indigo-200/40 blur-2xl" aria-hidden="true"></div>
        <div class="absolute -bottom-12 left-1/2 h-28 w-28 -translate-x-1/2 rounded-full bg-cyan-200/40 blur-2xl" aria-hidden="true"></div>

        <div class="relative space-y-5">
            <div class="flex flex-col gap-2">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-indigo-600">Scheduling Console</p>
                <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Viewing Requests Calendar</h2>
                <p class="max-w-3xl text-sm text-slate-600">Manage upcoming tours with a monthly timeline and keep confirmation actions in one place.</p>
            </div>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <article class="rounded-2xl border border-slate-200/80 bg-white/80 p-4 backdrop-blur">
                    <p class="text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Monthly Total</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $calendarStatusSummary['total'] }}</p>
                </article>
                <article class="rounded-2xl border border-amber-200/80 bg-amber-50/80 p-4">
                    <p class="text-xs font-medium uppercase tracking-[0.14em] text-amber-700">Pending</p>
                    <p class="mt-1 text-2xl font-semibold text-amber-800">{{ $calendarStatusSummary['pending'] }}</p>
                </article>
                <article class="rounded-2xl border border-emerald-200/80 bg-emerald-50/80 p-4">
                    <p class="text-xs font-medium uppercase tracking-[0.14em] text-emerald-700">Confirmed</p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-800">{{ $calendarStatusSummary['confirmed'] }}</p>
                </article>
                <article class="rounded-2xl border border-red-200/80 bg-red-50/80 p-4">
                    <p class="text-xs font-medium uppercase tracking-[0.14em] text-red-700">Cancelled</p>
                    <p class="mt-1 text-2xl font-semibold text-red-800">{{ $calendarStatusSummary['cancelled'] }}</p>
                </article>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-5">
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

            <div class="flex items-end">
                <button type="button" wire:click="clearDateRange" class="inline-flex h-11 w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700 transition hover:-translate-y-0.5 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">
                    Clear date range
                </button>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-[1.65fr_1fr]">
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">{{ $calendarMonthLabel }}</h3>
                    <p class="text-xs text-slate-500">Click a day to filter the table by that specific date.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="previousCalendarMonth" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition hover:-translate-y-0.5 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white" aria-label="Previous month">
                        <span aria-hidden="true">&larr;</span>
                    </button>
                    <button type="button" wire:click="jumpToCurrentMonth" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 text-xs font-semibold uppercase tracking-[0.14em] text-indigo-700 transition hover:-translate-y-0.5 hover:bg-indigo-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">
                        Today
                    </button>
                    <button type="button" wire:click="nextCalendarMonth" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition hover:-translate-y-0.5 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white" aria-label="Next month">
                        <span aria-hidden="true">&rarr;</span>
                    </button>
                </div>
            </div>

            <div class="mb-3 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>Pending</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>Confirmed</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>Cancelled</span>
            </div>

            <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">
                <span class="py-2">Mon</span>
                <span class="py-2">Tue</span>
                <span class="py-2">Wed</span>
                <span class="py-2">Thu</span>
                <span class="py-2">Fri</span>
                <span class="py-2">Sat</span>
                <span class="py-2">Sun</span>
            </div>

            <div class="grid grid-cols-7 gap-1">
                @foreach($calendarWeeks as $week)
                    @foreach($week as $day)
                        <button
                            type="button"
                            wire:click="selectCalendarDay('{{ $day['date'] }}')"
                            @class([
                                'min-h-28 rounded-xl border p-2.5 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-1',
                                'border-slate-200 bg-white hover:border-indigo-200 hover:bg-indigo-50/40' => $day['inMonth'] && ! $day['isSelectedDay'],
                                'border-slate-100 bg-slate-50/70 text-slate-400 hover:bg-slate-100/70' => ! $day['inMonth'] && ! $day['isSelectedDay'],
                                'border-indigo-400 bg-indigo-50 ring-1 ring-indigo-300' => $day['isSelectedDay'],
                                'ring-1 ring-amber-300/70' => ! $day['isSelectedDay'] && $day['isInActiveRange'],
                            ])
                        >
                            <div class="flex items-center justify-between">
                                <span @class([
                                    'inline-flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold',
                                    'text-slate-900' => $day['inMonth'],
                                    'text-slate-400' => ! $day['inMonth'],
                                    'bg-slate-900 text-white' => $day['isToday'],
                                ])>
                                    {{ $day['dayNumber'] }}
                                </span>
                                @if($day['summary']['total'] > 0)
                                    <span class="rounded-full bg-slate-900 px-2 py-0.5 text-[11px] font-semibold text-white">{{ $day['summary']['total'] }}</span>
                                @endif
                            </div>

                            @if($day['summary']['total'] > 0)
                                <div class="mt-2.5 space-y-1">
                                    @foreach($day['summary']['preview'] as $preview)
                                        <p class="truncate text-[11px] text-slate-700">
                                            {{ $preview['time'] }} {{ $preview['requester'] }}
                                        </p>
                                    @endforeach
                                    @if($day['summary']['total'] > count($day['summary']['preview']))
                                        <p class="text-[11px] font-medium text-indigo-600">+{{ $day['summary']['total'] - count($day['summary']['preview']) }} more</p>
                                    @endif
                                </div>
                            @else
                                <p class="mt-3 text-[11px] text-slate-400">No requests</p>
                            @endif
                        </button>
                    @endforeach
                @endforeach
            </div>
        </article>

        <aside class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-900">Upcoming Visits</h3>
                <span class="text-xs font-medium uppercase tracking-[0.14em] text-slate-500">next {{ count($upcomingRequests) }}</span>
            </div>

            <div class="space-y-2.5">
                @forelse($upcomingRequests as $request)
                    <article class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $request->requester_name }}</p>
                                <p class="text-xs text-slate-500">{{ $request->unit?->name ?? 'Deleted unit' }}</p>
                            </div>
                            @if($request->status === \App\Models\ViewingRequest::STATUS_CONFIRMED)
                                <span class="inline-flex items-center rounded-full border border-emerald-300 bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">CONFIRMED</span>
                            @elseif($request->status === \App\Models\ViewingRequest::STATUS_CANCELLED)
                                <span class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700">CANCELLED</span>
                            @else
                                <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">PENDING</span>
                            @endif
                        </div>
                        <p class="mt-2 text-xs text-slate-600">{{ $request->requested_start_at->format('M d, Y h:i A') }}</p>
                        <p class="text-xs text-slate-500">{{ $request->requested_end_at?->format('h:i A') ?: '-' }}</p>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                        No upcoming requests for current filters.
                    </div>
                @endforelse
            </div>
        </aside>
    </section>

    <section class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
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
    </section>

    <div>{{ $viewingRequests->links() }}</div>
</div>
