<?php

namespace App\Livewire\Admin\Rentals;

use App\Models\Rental;
use App\Models\Unit;
use App\Services\AuditLogger;
use App\Services\RentalAccessCodeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app.sidebar')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?string $unit_id = null;

    public string $renter_name = '';

    public ?string $contact_number = null;

    public string $id_type = 'PASSPORT';

    public ?string $id_last4 = null;

    public string $starts_at = '';

    public string $ends_at = '';

    /**
     * @var array<int, string>
     */
    public array $idTypeOptions = [
        'PASSPORT',
        'DRIVER_LICENSE',
        'NATIONAL_ID',
        'OTHER',
    ];

    public function mount(): void
    {
        $this->authorize('create', Rental::class);

        $this->starts_at = now()->addHour()->format('Y-m-d\TH:i');
        $this->ends_at = now()->addDay()->format('Y-m-d\TH:i');
    }

    public function save(RentalAccessCodeService $codes, AuditLogger $auditLogger): void
    {
        $this->authorize('create', Rental::class);

        $validated = $this->validate([
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'renter_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\\-()\\s]{7,20}$/'],
            'id_type' => ['required', 'in:'.implode(',', $this->idTypeOptions)],
            'id_last4' => ['nullable', 'regex:/^[A-Za-z0-9]{4}$/'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        $plainCode = $codes->generate();
        $rawCode = $codes->normalizeInput($plainCode);

        if ($rawCode === null) {
            $this->addError('renter_name', 'Unable to generate a secure access code. Please try again.');

            return;
        }

        $rental = DB::transaction(function () use ($validated, $plainCode, $rawCode, $codes): Rental {
            return Rental::create([
                'unit_id' => $validated['unit_id'] !== null ? (int) $validated['unit_id'] : null,
                'renter_name' => trim($validated['renter_name']),
                'contact_number' => isset($validated['contact_number']) && $validated['contact_number'] !== null
                    ? trim($validated['contact_number'])
                    : null,
                'id_type' => strtoupper($validated['id_type']),
                'id_last4' => isset($validated['id_last4']) && $validated['id_last4'] !== null
                    ? strtoupper($validated['id_last4'])
                    : null,
                'public_code_hash' => Hash::make($plainCode),
                'public_code_last4' => $codes->last4FromRaw($rawCode),
                'status' => Rental::STATUS_ACTIVE,
                'starts_at' => $validated['starts_at'],
                'ends_at' => $validated['ends_at'],
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        });

        $auditLogger->log(
            action: 'RENTAL_CREATED',
            unit: $rental->unit,
            changes: [
                'rental_id' => $rental->id,
                'id_type' => $rental->id_type,
                'public_code_last4' => $rental->public_code_last4,
                'starts_at' => optional($rental->starts_at)->toDateTimeString(),
                'ends_at' => optional($rental->ends_at)->toDateTimeString(),
                'status' => $rental->status,
            ]
        );

        session()->flash('issued_rental_code', $plainCode);
        session()->flash('status', 'Rental created. Copy and hand the renter access code now; it will not be shown again.');

        $this->redirectRoute('admin.rentals.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.rentals.form', [
            'units' => Unit::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
