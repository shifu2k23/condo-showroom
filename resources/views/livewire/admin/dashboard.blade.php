<div class="space-y-6 p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Admin Dashboard</h1>
        <div class="flex gap-2">
            <flux:button size="sm" variant="primary" :href="route('admin.units.create')" wire:navigate>Add Unit</flux:button>
            <flux:button size="sm" variant="ghost" :href="route('admin.categories.index')" wire:navigate>Categories</flux:button>
            <flux:button size="sm" variant="ghost" :href="route('admin.viewing-requests.index')" wire:navigate>Viewing Requests</flux:button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">Total Units</p>
            <p class="mt-1 text-2xl font-bold">{{ $totalUnits }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">Available</p>
            <p class="mt-1 text-2xl font-bold text-green-600">{{ $availableUnits }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">Unavailable</p>
            <p class="mt-1 text-2xl font-bold text-zinc-700 dark:text-zinc-300">{{ $unavailableUnits }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">Upcoming Viewings</p>
            <p class="mt-1 text-2xl font-bold">{{ $upcomingRequests }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">Requests This Week</p>
            <p class="mt-1 text-2xl font-bold">{{ $requestsThisWeek }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <section class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-3 text-lg font-semibold">Recent Activity</h2>
            <div class="space-y-2">
                @forelse($recentLogs as $log)
                    <div class="rounded-md border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                        <p class="font-medium">{{ $log->action }}</p>
                        <p class="text-zinc-500 dark:text-zinc-400">
                            {{ $log->unit?->name ?? 'No unit' }} • {{ $log->user?->name ?? 'System' }} • {{ $log->created_at->diffForHumans() }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No activity yet.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-3 text-lg font-semibold">Upcoming Viewings</h2>
            <div class="space-y-2">
                @forelse($upcomingViewings as $request)
                    <div class="rounded-md border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                        <p class="font-medium">{{ $request->unit?->name ?? 'Unit removed' }}</p>
                        <p class="text-zinc-500 dark:text-zinc-400">
                            {{ $request->requester_name }} • {{ $request->requested_start_at->format('M d, Y h:i A') }} • {{ $request->status }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No upcoming viewings.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
