<div class="space-y-8">
    <div class="flex flex-col gap-2">
        <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Analytics</h2>
        <p class="text-sm text-slate-500">Weekly and monthly performance overview for viewing requests, bookings, and tickets.</p>
    </div>

    @foreach ($sections as $section)
        @php
            $metrics = $section['metrics'] ?? [];
            $viewing = $metrics['viewing_requests'] ?? [];
            $rentals = $metrics['rentals'] ?? [];
            $tickets = $metrics['tickets'] ?? [];
            $topByRevenue = collect($metrics['top_units_by_revenue'] ?? []);
            $topByOccupancy = collect($metrics['top_units_by_occupancy'] ?? []);
            $trend = $section['trend'] ?? [];

            $requestTotals = collect($trend['request_totals'] ?? []);
            $requestConversionRates = collect($trend['request_conversion_rates'] ?? []);
            $revenueEstimates = collect($trend['revenue_estimates'] ?? []);
            $occupancyRates = collect($trend['occupancy_rates'] ?? []);
            $openTickets = collect($trend['open_tickets'] ?? []);
            $closedTickets = collect($trend['closed_tickets'] ?? []);
            $labels = collect($trend['labels'] ?? []);

            $requestMax = max(1, (int) $requestTotals->max());
            $revenueMax = max(1, (float) $revenueEstimates->max());
            $ticketMax = max(1, (int) max((int) $openTickets->max(), (int) $closedTickets->max()));
        @endphp

        <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-1">
                <h3 class="text-lg font-semibold text-slate-900">{{ $section['title'] }}</h3>
                <p class="text-sm text-slate-500">{{ $section['description'] }}</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.14em] text-slate-500">Viewing Requests</p>
                    <p class="mt-2 text-2xl font-semibold text-indigo-600">{{ (int) ($viewing['total'] ?? 0) }}</p>
                    <p class="mt-1 text-xs text-slate-500">Confirmed: {{ (int) ($viewing['confirmed'] ?? 0) }} | Cancelled: {{ (int) ($viewing['cancelled'] ?? 0) }}</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.14em] text-slate-500">Conversion Rate</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-600">{{ number_format((float) ($viewing['conversion_rate'] ?? 0), 2) }}%</p>
                    <p class="mt-1 text-xs text-slate-500">Request to confirmed ratio</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.14em] text-slate-500">Booked Nights / Occupancy</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) ($rentals['nights'] ?? 0) }}</p>
                    <p class="mt-1 text-xs text-slate-500">Occupancy: {{ number_format((float) ($rentals['occupancy_rate'] ?? 0), 2) }}%</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.14em] text-slate-500">Revenue Estimate</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">₱{{ number_format((float) ($rentals['revenue_estimate'] ?? 0), 2) }}</p>
                    <p class="mt-1 text-xs text-slate-500">Based on overlap nights and nightly rate</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.14em] text-slate-500">Open Tickets</p>
                    <p class="mt-2 text-2xl font-semibold text-amber-600">{{ (int) ($tickets['open'] ?? 0) }}</p>
                    <p class="mt-1 text-xs text-slate-500">OPEN + IN_PROGRESS</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.14em] text-slate-500">Closed Tickets</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-600">{{ (int) ($tickets['closed'] ?? 0) }}</p>
                    <p class="mt-1 text-xs text-slate-500">RESOLVED + CLOSED</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 md:col-span-2">
                    <p class="text-xs uppercase tracking-[0.14em] text-slate-500">Avg Resolution Time</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((float) ($tickets['avg_resolution_hours'] ?? 0), 2) }} hrs</p>
                    <p class="mt-1 text-xs text-slate-500">Average from created to last update for closed tickets</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Viewing Demand Trend</p>
                    <div class="mt-3 flex h-28 items-end gap-1">
                        @foreach ($requestTotals as $index => $value)
                            @php
                                $height = $requestMax > 0 ? max(8, (int) round(((int) $value / $requestMax) * 100)) : 8;
                            @endphp
                            <div class="group relative flex-1 rounded-t-md bg-indigo-500/80" style="height: {{ $height }}%">
                                <span class="pointer-events-none absolute -top-6 left-1/2 -translate-x-1/2 rounded bg-slate-900 px-1.5 py-0.5 text-[10px] text-white opacity-0 transition group-hover:opacity-100">{{ (int) $value }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3 grid grid-cols-4 gap-1 text-[10px] text-slate-500 sm:grid-cols-8">
                        @foreach ($labels as $label)
                            <span class="truncate">{{ $label }}</span>
                        @endforeach
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Conversion latest: {{ number_format((float) $requestConversionRates->last(), 2) }}%</p>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Booking Revenue Trend</p>
                    <div class="mt-3 flex h-28 items-end gap-1">
                        @foreach ($revenueEstimates as $value)
                            @php
                                $height = $revenueMax > 0 ? max(8, (int) round(((float) $value / $revenueMax) * 100)) : 8;
                            @endphp
                            <div class="group relative flex-1 rounded-t-md bg-emerald-500/80" style="height: {{ $height }}%">
                                <span class="pointer-events-none absolute -top-6 left-1/2 -translate-x-1/2 rounded bg-slate-900 px-1.5 py-0.5 text-[10px] text-white opacity-0 transition group-hover:opacity-100">₱{{ number_format((float) $value, 0) }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3 grid grid-cols-4 gap-1 text-[10px] text-slate-500 sm:grid-cols-8">
                        @foreach ($labels as $label)
                            <span class="truncate">{{ $label }}</span>
                        @endforeach
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Occupancy latest: {{ number_format((float) $occupancyRates->last(), 2) }}%</p>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Ticket Load Trend</p>
                    <div class="mt-3 flex h-28 items-end gap-1">
                        @foreach ($openTickets as $index => $openValue)
                            @php
                                $openHeight = $ticketMax > 0 ? max(6, (int) round(((int) $openValue / $ticketMax) * 100)) : 6;
                                $closedValue = (int) ($closedTickets[$index] ?? 0);
                                $closedHeight = $ticketMax > 0 ? max(6, (int) round(($closedValue / $ticketMax) * 100)) : 6;
                            @endphp
                            <div class="flex flex-1 items-end gap-1">
                                <div class="group relative w-1/2 rounded-t bg-amber-400/90" style="height: {{ $openHeight }}%">
                                    <span class="pointer-events-none absolute -top-6 left-1/2 -translate-x-1/2 rounded bg-slate-900 px-1.5 py-0.5 text-[10px] text-white opacity-0 transition group-hover:opacity-100">{{ (int) $openValue }}</span>
                                </div>
                                <div class="group relative w-1/2 rounded-t bg-sky-500/85" style="height: {{ $closedHeight }}%">
                                    <span class="pointer-events-none absolute -top-6 left-1/2 -translate-x-1/2 rounded bg-slate-900 px-1.5 py-0.5 text-[10px] text-white opacity-0 transition group-hover:opacity-100">{{ $closedValue }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3 flex items-center gap-3 text-[11px] text-slate-500">
                        <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-amber-400"></span>Open</span>
                        <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-sky-500"></span>Closed</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <div class="overflow-hidden rounded-xl border border-slate-200">
                    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Top Units by Revenue</p>
                    </div>
                    <div class="divide-y divide-slate-200">
                        @forelse ($topByRevenue as $unitRow)
                            <div class="flex items-center justify-between gap-2 px-4 py-3 text-sm">
                                <div>
                                    <p class="font-medium text-slate-900">{{ $unitRow['unit_name'] }}</p>
                                    <p class="text-xs text-slate-500">{{ (int) $unitRow['nights'] }} nights | {{ number_format((float) $unitRow['occupancy_rate'], 2) }}% occupancy</p>
                                </div>
                                <p class="font-semibold text-slate-900">₱{{ number_format((float) $unitRow['revenue_estimate'], 2) }}</p>
                            </div>
                        @empty
                            <p class="px-4 py-8 text-sm text-slate-500">No rental data for this period.</p>
                        @endforelse
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-slate-200">
                    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Top Units by Occupancy</p>
                    </div>
                    <div class="divide-y divide-slate-200">
                        @forelse ($topByOccupancy as $unitRow)
                            <div class="flex items-center justify-between gap-2 px-4 py-3 text-sm">
                                <div>
                                    <p class="font-medium text-slate-900">{{ $unitRow['unit_name'] }}</p>
                                    <p class="text-xs text-slate-500">{{ (int) $unitRow['nights'] }} nights | ₱{{ number_format((float) $unitRow['revenue_estimate'], 2) }}</p>
                                </div>
                                <p class="font-semibold text-slate-900">{{ number_format((float) $unitRow['occupancy_rate'], 2) }}%</p>
                            </div>
                        @empty
                            <p class="px-4 py-8 text-sm text-slate-500">No occupancy data for this period.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    @endforeach
</div>

