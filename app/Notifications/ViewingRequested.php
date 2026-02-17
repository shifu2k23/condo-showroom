<?php

namespace App\Notifications;

use App\Models\ViewingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ViewingRequested extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ViewingRequest $viewingRequest
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (config('broadcasting.default') !== 'null') {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload());
    }

    private function payload(): array
    {
        $unit = $this->viewingRequest->unit;

        return [
            'title' => 'New viewing request',
            'message' => sprintf(
                '%s requested %s on %s',
                $this->viewingRequest->requester_name,
                $unit?->name ?? 'a unit',
                optional($this->viewingRequest->requested_start_at)->format('M d, Y h:i A')
            ),
            'viewing_request_id' => $this->viewingRequest->id,
            'unit_id' => $this->viewingRequest->unit_id,
            'unit_name' => $unit?->name,
            'requested_start_at' => optional($this->viewingRequest->requested_start_at)->toIso8601String(),
            'requested_end_at' => optional($this->viewingRequest->requested_end_at)->toIso8601String(),
        ];
    }
}
