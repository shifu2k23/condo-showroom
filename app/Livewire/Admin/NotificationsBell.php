<?php

namespace App\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class NotificationsBell extends Component
{
    public function getListeners(): array
    {
        $user = auth()->user();

        if (! $user?->is_admin) {
            return [];
        }

        return [
            "echo-private:App.Models.User.{$user->id},.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated" => '$refresh',
        ];
    }

    public function markAllRead(): void
    {
        $user = auth()->user();
        if (! $user?->is_admin) {
            return;
        }

        $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function render(): View
    {
        $user = auth()->user();

        if (! $user?->is_admin) {
            return view('livewire.admin.notifications-bell', [
                'unreadCount' => 0,
                'recentNotifications' => collect(),
            ]);
        }

        return view('livewire.admin.notifications-bell', [
            'unreadCount' => $user->unreadNotifications()->count(),
            'recentNotifications' => $user->notifications()->latest()->limit(8)->get(),
        ]);
    }
}
