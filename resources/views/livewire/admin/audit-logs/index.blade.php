<div class="space-y-5 p-6">
    <h1 class="text-2xl font-semibold">Audit Logs</h1>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
        <flux:input wire:model.live.debounce.350ms="search" icon="magnifying-glass" placeholder="Search logs..." />
        <flux:select wire:model.live="actionFilter">
            <option value="">All actions</option>
            @foreach($actions as $action)
                <option value="{{ $action }}">{{ $action }}</option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="unitFilter">
            <option value="">All units</option>
            @foreach($units as $unit)
                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="userFilter">
            <option value="">All users</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">When</th>
                    <th class="px-4 py-3 text-left font-medium">Action</th>
                    <th class="px-4 py-3 text-left font-medium">Unit</th>
                    <th class="px-4 py-3 text-left font-medium">User</th>
                    <th class="px-4 py-3 text-left font-medium">Context</th>
                    <th class="px-4 py-3 text-left font-medium">Changes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($logs as $log)
                    <tr>
                        <td class="px-4 py-3">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td class="px-4 py-3 font-medium">{{ $log->action }}</td>
                        <td class="px-4 py-3">{{ $log->unit?->name ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="px-4 py-3 text-xs text-zinc-500">
                            <div>{{ $log->ip_address ?: '-' }}</div>
                            <div class="max-w-64 truncate">{{ $log->user_agent ?: '-' }}</div>
                        </td>
                        <td class="px-4 py-3 text-xs">
                            @if($log->changes)
                                <pre class="max-w-80 overflow-x-auto whitespace-pre-wrap text-zinc-500">{{ json_encode($log->changes, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                            @else
                                <span class="text-zinc-400">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-zinc-500">No audit logs yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $logs->links() }}</div>
</div>
