<div class="space-y-8">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Overview</p>
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Admin Dashboard</h2>
        </div>

        <div class="flex flex-wrap items-center justify-start gap-2 lg:justify-end">
            <a href="{{ route('admin.units.create') }}" wire:navigate class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-indigo-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50">Add Unit</a>
            <a href="{{ route('admin.categories.index') }}" wire:navigate class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition duration-200 hover:-translate-y-0.5 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50">Categories</a>
            <a href="{{ route('admin.viewing-requests.index') }}" wire:navigate class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition duration-200 hover:-translate-y-0.5 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50">Viewing Requests</a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5">
            <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Total Units</p>
            <p class="mt-3 text-3xl font-bold text-indigo-600">{{ $totalUnits }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5">
            <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Available</p>
            <p class="mt-3 text-3xl font-bold text-emerald-600">{{ $availableUnits }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5">
            <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Unavailable</p>
            <p class="mt-3 text-3xl font-bold text-red-600">{{ $unavailableUnits }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5">
            <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Upcoming Viewings</p>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ $upcomingRequests }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5">
            <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Requests This Week</p>
            <p class="mt-3 text-3xl font-bold text-indigo-600">{{ $requestsThisWeek }}</p>
        </div>
    </div>

    @if($showroomLink || $renterAccessLink)
        <section class="rounded-2xl border border-indigo-100 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-slate-900">Shareable Links</h3>
                <p class="mt-1 text-sm text-slate-500">Share these links publicly to open your tenant showroom directly.</p>
            </div>

            <div class="space-y-4">
                @if($showroomLink)
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Public Showroom</p>
                        <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center">
                            <input type="text" value="{{ $showroomLink }}" readonly class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700">
                            <div class="flex items-center gap-2">
                                <a href="{{ $showroomLink }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">Open</a>
                                <button type="button" onclick="navigator.clipboard.writeText('{{ $showroomLink }}')" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-600 px-3 text-xs font-semibold text-white transition hover:bg-indigo-500">Copy</button>
                            </div>
                        </div>
                    </div>
                @endif

                @if($renterAccessLink)
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Renter Access</p>
                        <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center">
                            <input type="text" value="{{ $renterAccessLink }}" readonly class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700">
                            <div class="flex items-center gap-2">
                                <a href="{{ $renterAccessLink }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">Open</a>
                                <button type="button" onclick="navigator.clipboard.writeText('{{ $renterAccessLink }}')" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-600 px-3 text-xs font-semibold text-white transition hover:bg-indigo-500">Copy</button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-slate-900">Recent Activity</h3>
            <div class="divide-y divide-slate-200">
                @forelse($recentLogs as $log)
                    <div class="space-y-1 py-3 text-sm">
                        <p class="font-medium text-slate-900">{{ $log->action }}</p>
                        <p class="text-slate-500">{{ $log->unit?->name ?? 'No unit' }} | {{ $log->user?->name ?? 'System' }} | {{ $log->created_at->diffForHumans() }}</p>
                    </div>
                @empty
                    <p class="py-8 text-sm text-slate-500">No activity yet.</p>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-slate-900">Upcoming Viewings</h3>
            <div class="divide-y divide-slate-200">
                @forelse($upcomingViewings as $request)
                    <div class="space-y-1 py-3 text-sm">
                        <p class="font-medium text-slate-900">{{ $request->unit?->name ?? 'Unit removed' }}</p>
                        <p class="text-slate-500">{{ $request->requester_name }} | {{ $request->requested_start_at->format('M d, Y h:i A') }} | {{ $request->status }}</p>
                    </div>
                @empty
                    <p class="py-8 text-sm text-slate-500">No upcoming viewings.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
