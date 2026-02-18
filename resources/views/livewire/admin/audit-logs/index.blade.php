<div class="space-y-7">
    <div class="flex flex-col gap-2">
        <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Audit Logs</h2>
        <p class="text-sm text-slate-500">Track admin and system events across the platform.</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-4">
            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m21 21-4.35-4.35m1.35-5.15a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                <input type="search" wire:model.live.debounce.350ms="search" placeholder="Search logs..." aria-label="Search logs" class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-3 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
            </div>

            <select wire:model.live="actionFilter" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                <option value="">All actions</option>
                @foreach($actions as $action)
                    <option value="{{ $action }}">{{ $action }}</option>
                @endforeach
            </select>

            <select wire:model.live="unitFilter" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                <option value="">All units</option>
                @foreach($units as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="userFilter" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                <option value="">All users</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-[0.14em] text-slate-500">
                    <th scope="col" class="px-4 py-3 font-medium">When</th>
                    <th scope="col" class="px-4 py-3 font-medium">Action</th>
                    <th scope="col" class="px-4 py-3 font-medium">Unit</th>
                    <th scope="col" class="px-4 py-3 font-medium">User</th>
                    <th scope="col" class="px-4 py-3 font-medium">Context</th>
                    <th scope="col" class="px-4 py-3 font-medium">Changes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr class="border-b border-slate-200 text-slate-700 transition duration-150 hover:bg-slate-50">
                        <td class="px-4 py-3 text-slate-700">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $log->action }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $log->unit?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="px-4 py-3 text-xs text-slate-500">
                            <div>{{ $log->ip_address ?: '-' }}</div>
                            <div class="max-w-64 truncate">{{ $log->user_agent ?: '-' }}</div>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">
                            @if($log->changes)
                                <pre class="max-w-80 overflow-x-auto whitespace-pre-wrap text-xs leading-5 text-slate-500">{{ json_encode($log->changes, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No audit logs yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $logs->links() }}</div>
</div>
