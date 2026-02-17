<?php

namespace App\Application\Viewing;

use App\Application\Audit\AuditLogWriter;
use App\Application\Viewing\Data\CreateViewingRequestData;
use App\Models\Unit;
use App\Models\User;
use App\Models\ViewingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ViewingRequestService
{
    public function __construct(
        private readonly AuditLogWriter $auditLogWriter
    ) {}

    public function create(CreateViewingRequestData $data, bool $blockPendingOverlap = false): ViewingRequest
    {
        // Transaction boundary:
        // - lock unit
        // - overlap query with lockForUpdate
        // - create viewing request
        // - write audit log
        return DB::transaction(function () use ($data, $blockPendingOverlap): ViewingRequest {
            $unit = Unit::query()->lockForUpdate()->findOrFail($data->unitId);
            $endAt = $data->requestedEndAt ?? $data->requestedStartAt->addHour();

            if ($data->requestedStartAt->lte(now())) {
                throw ValidationException::withMessages([
                    'requested_start_at' => 'Requested start must be in the future.',
                ]);
            }

            if ($endAt->lte($data->requestedStartAt)) {
                throw ValidationException::withMessages([
                    'requested_end_at' => 'Requested end must be after requested start.',
                ]);
            }

            $this->assertNoOverlap(
                unitId: $unit->id,
                startAt: $data->requestedStartAt->toDateTimeString(),
                endAt: $endAt->toDateTimeString(),
                blockPendingOverlap: $blockPendingOverlap
            );

            $viewingRequest = ViewingRequest::query()->create([
                'unit_id' => $unit->id,
                'requester_name' => $data->requesterName,
                'requester_phone' => $data->requesterPhone,
                'requester_email' => $data->requesterEmail,
                'requested_start_at' => $data->requestedStartAt,
                'requested_end_at' => $endAt,
                'status' => ViewingRequest::STATUS_PENDING,
                'notes' => $data->notes,
                'ip_address' => $data->ipAddress,
            ]);

            $this->auditLogWriter->write(
                action: 'VIEWING_REQUEST_CREATED',
                unitId: $unit->id,
                userId: null,
                changes: [
                    'viewing_request_id' => $viewingRequest->id,
                    'status' => $viewingRequest->status,
                    'requested_start_at' => $data->requestedStartAt->toDateTimeString(),
                    'requested_end_at' => $endAt->toDateTimeString(),
                ],
                ipAddress: $data->ipAddress,
                userAgent: $data->userAgent,
            );

            DB::afterCommit(function (): void {
                // Notification / broadcast side effects should happen only after commit.
                // Example: send admin notifications for $viewingRequest.
            });

            return $viewingRequest;
        });
    }

    public function confirm(
        int $viewingRequestId,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ViewingRequest {
        // Transaction boundary:
        // - lock target request
        // - transition status
        // - write audit log
        return DB::transaction(function () use ($viewingRequestId, $actor, $ipAddress, $userAgent): ViewingRequest {
            $viewingRequest = ViewingRequest::query()->lockForUpdate()->findOrFail($viewingRequestId);

            $viewingRequest->update([
                'status' => ViewingRequest::STATUS_CONFIRMED,
            ]);

            $this->auditLogWriter->write(
                action: 'VIEWING_REQUEST_CONFIRMED',
                unitId: $viewingRequest->unit_id,
                userId: $actor->id,
                changes: [
                    'viewing_request_id' => $viewingRequest->id,
                    'status' => $viewingRequest->status,
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            return $viewingRequest;
        });
    }

    public function cancel(
        int $viewingRequestId,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ViewingRequest {
        // Transaction boundary:
        // - lock target request
        // - transition status
        // - write audit log
        return DB::transaction(function () use ($viewingRequestId, $actor, $ipAddress, $userAgent): ViewingRequest {
            $viewingRequest = ViewingRequest::query()->lockForUpdate()->findOrFail($viewingRequestId);

            $viewingRequest->update([
                'status' => ViewingRequest::STATUS_CANCELLED,
            ]);

            $this->auditLogWriter->write(
                action: 'VIEWING_REQUEST_CANCELLED',
                unitId: $viewingRequest->unit_id,
                userId: $actor->id,
                changes: [
                    'viewing_request_id' => $viewingRequest->id,
                    'status' => $viewingRequest->status,
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            return $viewingRequest;
        });
    }

    private function assertNoOverlap(
        int $unitId,
        string $startAt,
        string $endAt,
        bool $blockPendingOverlap = false,
    ): void {
        $blockedStatuses = [ViewingRequest::STATUS_CONFIRMED];

        if ($blockPendingOverlap) {
            $blockedStatuses[] = ViewingRequest::STATUS_PENDING;
        }

        $hasOverlap = ViewingRequest::query()
            ->where('unit_id', $unitId)
            ->whereIn('status', $blockedStatuses)
            ->where('requested_start_at', '<', $endAt)
            ->where(function ($query) use ($startAt): void {
                $query->whereNull('requested_end_at')
                    ->orWhere('requested_end_at', '>', $startAt);
            })
            ->lockForUpdate()
            ->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'requested_start_at' => 'Requested schedule overlaps with an existing viewing.',
            ]);
        }
    }
}
