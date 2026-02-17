<div wire:poll.10s>
    <flux:dropdown position="bottom" align="end">
        <flux:button variant="ghost" size="sm" icon="bell">
            @if($unreadCount > 0)
                <span class="ml-2 inline-flex rounded-full bg-red-500 px-2 py-0.5 text-xs font-semibold text-white">
                    {{ $unreadCount }}
                </span>
            @endif
        </flux:button>

        <flux:menu class="w-80">
            <div class="flex items-center justify-between px-3 py-2">
                <flux:heading size="sm">Notifications</flux:heading>
                @if($unreadCount > 0)
                    <flux:button size="xs" variant="ghost" wire:click="markAllRead">Mark all read</flux:button>
                @endif
            </div>
            <flux:menu.separator />
            @forelse($recentNotifications as $notification)
                <div class="px-3 py-2 text-sm {{ is_null($notification->read_at) ? 'bg-zinc-100 dark:bg-zinc-800' : '' }}">
                    <p class="font-medium">{{ $notification->data['title'] ?? 'Notification' }}</p>
                    <p class="text-zinc-500 dark:text-zinc-400">{{ $notification->data['message'] ?? '' }}</p>
                </div>
            @empty
                <div class="px-3 py-4 text-sm text-zinc-500 dark:text-zinc-400">No notifications yet.</div>
            @endforelse
        </flux:menu>
    </flux:dropdown>
</div>
