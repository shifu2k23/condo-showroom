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
