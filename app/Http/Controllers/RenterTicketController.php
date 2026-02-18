<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceTicket;
use App\Models\Rental;
use App\Services\AuditLogger;
use App\Services\RenterAccessService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RenterTicketController extends Controller
{
    public function store(Request $request, RenterAccessService $renterAccess, AuditLogger $auditLogger): RedirectResponse
    {
        $rental = $renterAccess->resolveRentalFromBrowserSession(allowExpiredRental: true);
        if (! $rental) {
            $renterAccess->clearBrowserSession();

            return redirect()
                ->route('renter.access')
                ->with('status', 'Your renter session has expired. Please sign in again.');
        }

        if ($this->isExpired($rental)) {
            return back()->withErrors([
                'subject' => $this->expiredMessage(),
            ]);
        }

        $validated = $request->validate([
            'category' => ['required', 'in:CLEANING,PLUMBING,ELECTRICAL,OTHER'],
            'subject' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'image', 'max:3072'],
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')?->store('maintenance-tickets', 'local');
        }

        $ticket = MaintenanceTicket::query()->create([
            'rental_id' => $rental->id,
            'unit_id' => $rental->unit_id,
            'status' => MaintenanceTicket::STATUS_OPEN,
            'category' => $validated['category'],
            'subject' => trim($validated['subject']),
            'description' => trim($validated['description']),
            'attachment_path' => $attachmentPath,
        ]);

        $auditLogger->log(
            action: 'RENTER_TICKET_CREATED',
            unit: $rental->unit,
            changes: [
                'ticket_id' => $ticket->id,
                'rental_id' => $rental->id,
                'category' => $ticket->category,
                'status' => $ticket->status,
                'has_attachment' => $attachmentPath !== null,
            ]
        );

        return redirect()
            ->route('renter.tickets')
            ->with('status', 'Maintenance ticket submitted successfully.');
    }

    private function isExpired(Rental $rental): bool
    {
        if ($rental->status !== Rental::STATUS_ACTIVE) {
            return true;
        }

        return CarbonImmutable::now()->gt(CarbonImmutable::instance($rental->ends_at));
    }

    private function expiredMessage(): string
    {
        return 'Your rental period has ended. Ticket submission is unavailable. Please contact the front desk if you need assistance.';
    }
}
