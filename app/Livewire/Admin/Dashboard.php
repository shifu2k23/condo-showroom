<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Unit;
use App\Models\ViewingRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app.sidebar')]
class Dashboard extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('access-admin');
    }

    public function render()
    {
        $totalUnits = Unit::query()->count();
        $availableUnits = Unit::query()->where('status', Unit::STATUS_AVAILABLE)->count();
        $unavailableUnits = Unit::query()->where('status', Unit::STATUS_UNAVAILABLE)->count();
        $upcomingRequests = ViewingRequest::query()
            ->whereIn('status', [ViewingRequest::STATUS_PENDING, ViewingRequest::STATUS_CONFIRMED])
            ->where('requested_start_at', '>=', now())
            ->count();
        $requestsThisWeek = ViewingRequest::query()
            ->whereBetween('requested_start_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $recentLogs = AuditLog::query()
            ->with(['unit', 'user'])
            ->latest()
            ->limit(10)
            ->get();

        $upcomingViewings = ViewingRequest::query()
            ->with('unit')
            ->whereIn('status', [ViewingRequest::STATUS_PENDING, ViewingRequest::STATUS_CONFIRMED])
            ->where('requested_start_at', '>=', now())
            ->orderBy('requested_start_at')
            ->limit(10)
            ->get();

        $tenantSlug = (string) (auth()->user()?->tenant?->slug ?? '');
        $showroomLink = $tenantSlug !== ''
            ? route('home', ['tenant' => $tenantSlug], absolute: true)
            : null;
        $renterAccessLink = $tenantSlug !== ''
            ? route('renter.access', ['tenant' => $tenantSlug], absolute: true)
            : null;

        return view('livewire.admin.dashboard', [
            'totalUnits' => $totalUnits,
            'availableUnits' => $availableUnits,
            'unavailableUnits' => $unavailableUnits,
            'upcomingRequests' => $upcomingRequests,
            'requestsThisWeek' => $requestsThisWeek,
            'recentLogs' => $recentLogs,
            'upcomingViewings' => $upcomingViewings,
            'showroomLink' => $showroomLink,
            'renterAccessLink' => $renterAccessLink,
        ]);
    }
}
