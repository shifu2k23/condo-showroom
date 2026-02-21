<?php

namespace App\Livewire\Public;

use App\Models\MaintenanceTicket;
use App\Models\Rental;
use App\Services\AuditLogger;
use App\Services\RenterAccessService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.public')]
class RenterTickets extends Component
{
    use WithFileUploads;
    use WithPagination;

    public ?Rental $rental = null;

    public string $category = MaintenanceTicket::CATEGORY_CLEANING;

    public string $subject = '';

    public string $description = '';

    public $attachment = null;

    public ?string $statusMessage = null;

    /**
     * @var array<int, string>
     */
    public array $categoryOptions = [
        MaintenanceTicket::CATEGORY_CLEANING,
        MaintenanceTicket::CATEGORY_PLUMBING,
        MaintenanceTicket::CATEGORY_ELECTRICAL,
        MaintenanceTicket::CATEGORY_OTHER,
    ];

    public function mount(RenterAccessService $renterAccess): void
    {
        $this->rental = $renterAccess->resolveRentalFromBrowserSession();

        if (session()->has('status')) {
            $this->statusMessage = (string) session('status');
        }
    }

    public function submit(AuditLogger $auditLogger): void
    {
        $this->resetErrorBag();

        if (! $this->rental) {
            $this->addError('subject', 'Unable to submit ticket. Please sign in again.');

            return;
        }

        if ($this->isAccessExpired()) {
            $this->addError('subject', $this->expiredTicketMessage());

            return;
        }

        $validated = $this->validate([
            'category' => ['required', 'in:'.implode(',', $this->categoryOptions)],
            'subject' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'image', 'max:3072'],
        ]);

        $attachmentPath = null;
        if ($this->attachment) {
            $attachmentPath = $this->attachment->store('maintenance-tickets', 'local');
        }

        $ticket = MaintenanceTicket::query()->create([
            'rental_id' => $this->rental->id,
            'unit_id' => $this->rental->unit_id,
            'status' => MaintenanceTicket::STATUS_OPEN,
            'category' => $validated['category'],
            'subject' => trim($validated['subject']),
            'description' => trim($validated['description']),
            'attachment_path' => $attachmentPath,
        ]);

        $auditLogger->log(
            action: 'RENTER_TICKET_CREATED',
            unit: $this->rental->unit,
            changes: [
                'ticket_id' => $ticket->id,
                'rental_id' => $this->rental->id,
                'category' => $ticket->category,
                'status' => $ticket->status,
                'has_attachment' => $attachmentPath !== null,
            ]
        );

        $this->reset(['subject', 'description', 'attachment']);
        $this->category = MaintenanceTicket::CATEGORY_CLEANING;
        $this->statusMessage = 'Maintenance ticket submitted successfully.';
        $this->resetPage();
    }

    public function logout(RenterAccessService $renterAccess): void
    {
        $renterAccess->clearBrowserSession();

        $this->redirectRoute('renter.access', navigate: true);
    }

    public function attachmentUrl(?string $attachmentPath): ?string
    {
        if (! $attachmentPath) {
            return null;
        }

        try {
            return Storage::disk('local')->temporaryUrl($attachmentPath, now()->addMinutes(5));
        } catch (\Throwable) {
            return null;
        }
    }

    public function isAccessExpired(): bool
    {
        if (! $this->rental) {
            return true;
        }

        if ($this->rental->status !== Rental::STATUS_ACTIVE) {
            return true;
        }

        return CarbonImmutable::now()->gt(CarbonImmutable::instance($this->rental->ends_at));
    }

    public function expiredTicketMessage(): string
    {
        return 'Your rental period has ended. Ticket submission is unavailable. Please contact the front desk if you need assistance.';
    }

    public function render()
    {
        $tickets = $this->rental
            ? $this->rental->maintenanceTickets()->latest('created_at')->paginate(8)
            : MaintenanceTicket::query()->whereRaw('1 = 0')->paginate(8);

        return view('livewire.public.renter-tickets', [
            'tickets' => $tickets,
        ]);
    }
}
