<?php

namespace App\Livewire\Public;

use App\Models\Unit;
use App\Services\PricingCalculator;
use App\Services\ViewingRequestService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class UnitShow extends Component
{
    private const SUBMIT_LIMIT_PER_MINUTE = 5;

    private const SUBMIT_LIMIT_PER_HOUR = 20;

    private const SUBMIT_LIMIT_MINUTE_DECAY_SECONDS = 60;

    private const SUBMIT_LIMIT_HOUR_DECAY_SECONDS = 3600;

    public Unit $unit;

    public ?string $checkIn = null;

    public ?string $checkOut = null;

    public ?int $estimatedPrice = null;

    public string $estimateBreakdown = '';

    public string $estimateError = '';

    public ?string $requestDate = null;

    public ?string $requestTime = null;

    public string $clientName = '';

    public ?string $clientEmail = null;

    public ?string $clientPhone = null;

    public ?string $clientNotes = null;

    public string $website = '';

    public int $formLoadedAt = 0;

    public array $timeSlots = [
        '09:00',
        '10:00',
        '11:00',
        '13:00',
        '14:00',
        '15:00',
        '16:00',
    ];

    public bool $requestSuccess = false;

    public function mount(Unit $unit): void
    {
        $tenant = request()->route('tenant');
        if (! $tenant || (int) $tenant->getKey() !== (int) $unit->tenant_id) {
            abort(404);
        }

        $this->unit = $unit->load([
            'category',
            'images' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        if ($this->unit->images->isEmpty()) {
            abort(404);
        }

        $this->formLoadedAt = now()->timestamp;
    }

    public function updatedCheckIn(): void
    {
        $this->calculatePrice();
    }

    public function updatedCheckOut(): void
    {
        $this->calculatePrice();
    }

    public function calculatePrice(): void
    {
        $this->estimateError = '';
        $this->estimatedPrice = null;
        $this->estimateBreakdown = '';

        if (! $this->checkIn || ! $this->checkOut) {
            return;
        }

        try {
            $start = CarbonImmutable::parse($this->checkIn);
            $end = CarbonImmutable::parse($this->checkOut);
            $calculator = app(PricingCalculator::class);
            $result = $calculator->calculate($this->unit, $start, $end);

            if ($result['nights'] <= 0) {
                $this->estimateError = 'Check-out date must be after check-in date.';

                return;
            }

            $this->estimatedPrice = $result['total'];
            $this->estimateBreakdown = $result['breakdown'];
        } catch (\Throwable $exception) {
            $this->estimateError = 'Unable to calculate estimate for the selected dates.';
        }
    }

    public function submitRequest(ViewingRequestService $service): void
    {
        try {
            $executed = $this->executeWithinSubmissionRateLimit(function () use ($service): void {
                $validated = $this->validate([
                    'requestDate' => ['required', 'date'],
                    'requestTime' => ['required', 'date_format:H:i', Rule::in($this->timeSlots)],
                    'clientName' => ['required', 'string', 'max:255'],
                    'clientEmail' => ['nullable', 'email', 'max:255', 'required_without:clientPhone'],
                    'clientPhone' => ['nullable', 'string', 'max:20', 'regex:/^(?:\+?63|0)\d{10}$/', 'required_without:clientEmail'],
                    'clientNotes' => ['nullable', 'string', 'max:2000'],
                ]);

                $this->guardAgainstSpam();

                $startAt = CarbonImmutable::parse("{$validated['requestDate']} {$validated['requestTime']}");
                $sanitizedNotes = isset($validated['clientNotes']) && $validated['clientNotes'] !== null
                    ? trim(strip_tags($validated['clientNotes']))
                    : null;

                $service->create([
                    'unit_id' => $this->unit->id,
                    'requested_start_at' => $startAt->toDateTimeString(),
                    'requested_end_at' => $startAt->addHour()->toDateTimeString(),
                    'requester_name' => trim($validated['clientName']),
                    'requester_email' => $validated['clientEmail'] ?? null,
                    'requester_phone' => isset($validated['clientPhone'])
                        ? preg_replace('/\s+/', '', $validated['clientPhone'])
                        : null,
                    'notes' => $sanitizedNotes,
                ], request());
            });

            if (! $executed) {
                $this->addError('requestDate', 'Too many requests from this IP. Please try again shortly.');

                return;
            }

            $this->requestSuccess = true;
            $this->reset([
                'requestDate',
                'requestTime',
                'clientName',
                'clientEmail',
                'clientPhone',
                'clientNotes',
                'website',
            ]);
            $this->formLoadedAt = now()->timestamp;
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }
        } catch (\Throwable $exception) {
            $this->addError('requestDate', 'Unable to submit viewing request right now. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.public.unit-show');
    }

    private function executeWithinSubmissionRateLimit(callable $callback): bool
    {
        [$minuteKey, $hourKey] = $this->submissionRateLimitKeys();

        if (
            RateLimiter::tooManyAttempts($minuteKey, self::SUBMIT_LIMIT_PER_MINUTE)
            || RateLimiter::tooManyAttempts($hourKey, self::SUBMIT_LIMIT_PER_HOUR)
        ) {
            return false;
        }

        return (bool) RateLimiter::attempt(
            $hourKey,
            self::SUBMIT_LIMIT_PER_HOUR,
            function () use ($minuteKey, $callback): bool {
                return (bool) RateLimiter::attempt(
                    $minuteKey,
                    self::SUBMIT_LIMIT_PER_MINUTE,
                    function () use ($callback): bool {
                        $callback();

                        return true;
                    },
                    self::SUBMIT_LIMIT_MINUTE_DECAY_SECONDS
                );
            },
            self::SUBMIT_LIMIT_HOUR_DECAY_SECONDS
        );
    }

    private function submissionRateLimitKeys(): array
    {
        $ip = request()->ip() ?? 'unknown';
        $fingerprint = hash('sha256', $ip.'|'.$this->unit->getKey());

        return [
            'viewing-request:minute:'.$fingerprint,
            'viewing-request:hour:'.$fingerprint,
        ];
    }

    private function guardAgainstSpam(): void
    {
        if ($this->website !== '') {
            throw ValidationException::withMessages([
                'requestDate' => 'Unable to submit viewing request right now.',
            ]);
        }

        if ($this->formLoadedAt > 0 && now()->timestamp - $this->formLoadedAt < 2) {
            throw ValidationException::withMessages([
                'requestDate' => 'Please wait a moment before submitting your request.',
            ]);
        }
    }
}
