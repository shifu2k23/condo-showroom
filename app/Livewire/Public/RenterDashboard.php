<?php

namespace App\Livewire\Public;

use App\Models\Rental;
use App\Services\RenterAccessService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class RenterDashboard extends Component
{
    public ?Rental $rental = null;

    public function mount(RenterAccessService $renterAccess): void
    {
        $this->rental = $renterAccess->resolveRentalFromBrowserSession();
    }

    public function logout(RenterAccessService $renterAccess): void
    {
        $renterAccess->clearBrowserSession();

        $this->redirectRoute('renter.access', navigate: true);
    }

    public function getIsExpiredProperty(): bool
    {
        if (! $this->rental) {
            return true;
        }

        if ($this->rental->status !== Rental::STATUS_ACTIVE) {
            return true;
        }

        return CarbonImmutable::now()->gt(CarbonImmutable::instance($this->rental->ends_at));
    }

    public function render()
    {
        /** @var Collection<int, \App\Models\MaintenanceTicket> $recentTickets */
        $recentTickets = $this->rental
            ? $this->rental->maintenanceTickets()->latest('created_at')->limit(5)->get()
            : collect();

        return view('livewire.public.renter-dashboard', [
            'recentTickets' => $recentTickets,
        ]);
    }
}
