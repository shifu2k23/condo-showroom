<?php

namespace App\Services;

use App\Models\User;
use App\Models\ViewingRequest;
use App\Notifications\ViewingRequested;
use App\Support\Tenancy\TenantManager;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ViewingRequestService
{
    public function __construct(
        private readonly AuditLogger $auditLogger
    ) {}

    public function create(array $attributes, ?Request $request = null, bool $checkPendingOverlap = false): ViewingRequest
    {
        $validated = Validator::make($attributes, [
            'unit_id' => [
                'required',
                'integer',
                Rule::exists('units', 'id')->where(
                    'tenant_id',
                    app(TenantManager::class)->currentId()
                ),
            ],
            'requested_start_at' => ['required', 'date'],
            'requested_end_at' => ['nullable', 'date'],
            'requester_name' => ['required', 'string', 'max:255'],
            'requester_phone' => ['nullable', 'string', 'max:20', 'regex:/^(?:\+?63|0)\d{10}$/', 'required_without:requester_email'],
            'requester_email' => ['nullable', 'email', 'max:255', 'required_without:requester_phone'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        $validated['requester_name'] = trim($validated['requester_name']);
        $validated['requester_phone'] = isset($validated['requester_phone']) && $validated['requester_phone'] !== null
            ? preg_replace('/\s+/', '', $validated['requester_phone'])
            : null;
        $validated['notes'] = isset($validated['notes']) && $validated['notes'] !== null
            ? trim(strip_tags($validated['notes']))
            : null;
        $validated['ip_address'] = $request?->ip();

        $startAt = CarbonImmutable::parse($validated['requested_start_at']);
        $endAt = isset($validated['requested_end_at']) && $validated['requested_end_at']
            ? CarbonImmutable::parse($validated['requested_end_at'])
            : $startAt->addHour();

        if ($startAt->lte(now())) {
            throw ValidationException::withMessages([
                'requested_start_at' => 'Viewing date must be in the future.',
            ]);
        }

        if ($endAt->lte($startAt)) {
            throw ValidationException::withMessages([
                'requested_end_at' => 'Viewing end time must be after start time.',
            ]);
        }

        $validated['requested_start_at'] = $startAt;
        $validated['requested_end_at'] = $endAt;
        $validated['status'] = ViewingRequest::STATUS_PENDING;

        $statusesToBlock = [ViewingRequest::STATUS_CONFIRMED];
        if ($checkPendingOverlap) {
            $statusesToBlock[] = ViewingRequest::STATUS_PENDING;
        }

        return DB::transaction(function () use ($validated, $statusesToBlock, $request): ViewingRequest {
            $hasOverlap = ViewingRequest::query()
                ->where('unit_id', $validated['unit_id'])
                ->whereIn('status', $statusesToBlock)
                ->where(function ($query) use ($validated) {
                    $query->where('requested_start_at', '<', $validated['requested_end_at'])
                        ->where(function ($nested) use ($validated) {
                            $nested->whereNull('requested_end_at')
                                ->orWhere('requested_end_at', '>', $validated['requested_start_at']);
                        });
                })
                ->lockForUpdate()
                ->exists();

            if ($hasOverlap) {
                throw ValidationException::withMessages([
                    'requested_start_at' => 'This time overlaps with an existing viewing schedule.',
                ]);
            }

            $viewingRequest = ViewingRequest::create($validated);
            $viewingRequest->load('unit');

            $this->auditLogger->log(
                action: 'VIEWING_REQUEST_CREATED',
                unit: $viewingRequest->unit,
                changes: [
                    'viewing_request_id' => $viewingRequest->id,
                    'status' => $viewingRequest->status,
                    'requested_start_at' => $viewingRequest->requested_start_at?->toDateTimeString(),
                    'requested_end_at' => $viewingRequest->requested_end_at?->toDateTimeString(),
                ],
                request: $request
            );

            $this->notifyAdmins($viewingRequest);

            return $viewingRequest;
        });
    }

    public function confirm(ViewingRequest $viewingRequest, ?Request $request = null, ?User $actor = null): ViewingRequest
    {
        $actor ??= $request?->user();
        if (! $actor) {
            throw new AuthorizationException('Unauthorized action.');
        }

        Gate::forUser($actor)->authorize('confirm', $viewingRequest);

        return DB::transaction(function () use ($viewingRequest, $request, $actor): ViewingRequest {
            $locked = ViewingRequest::query()->whereKey($viewingRequest->id)->lockForUpdate()->firstOrFail();

            $locked->update(['status' => ViewingRequest::STATUS_CONFIRMED]);
            $locked->load('unit');

            $this->auditLogger->log(
                action: 'VIEWING_REQUEST_CONFIRMED',
                unit: $locked->unit,
                changes: [
                    'viewing_request_id' => $locked->id,
                    'status' => $locked->status,
                ],
                user: $actor,
                request: $request
            );

            return $locked;
        });
    }

    public function cancel(ViewingRequest $viewingRequest, ?Request $request = null, ?User $actor = null): ViewingRequest
    {
        $actor ??= $request?->user();
        if (! $actor) {
            throw new AuthorizationException('Unauthorized action.');
        }

        Gate::forUser($actor)->authorize('cancel', $viewingRequest);

        return DB::transaction(function () use ($viewingRequest, $request, $actor): ViewingRequest {
            $locked = ViewingRequest::query()->whereKey($viewingRequest->id)->lockForUpdate()->firstOrFail();

            $locked->update(['status' => ViewingRequest::STATUS_CANCELLED]);
            $locked->load('unit');

            $this->auditLogger->log(
                action: 'VIEWING_REQUEST_CANCELLED',
                unit: $locked->unit,
                changes: [
                    'viewing_request_id' => $locked->id,
                    'status' => $locked->status,
                ],
                user: $actor,
                request: $request
            );

            return $locked;
        });
    }

    private function notifyAdmins(ViewingRequest $viewingRequest): void
    {
        $tenantId = app(TenantManager::class)->currentId();

        $admins = User::query()
            ->where('is_admin', true)
            ->where('is_super_admin', false)
            ->where('tenant_id', $tenantId)
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new ViewingRequested($viewingRequest));
    }
}
