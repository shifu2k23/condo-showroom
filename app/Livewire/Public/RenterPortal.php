<?php

namespace App\Livewire\Public;

use App\Models\Rental;
use App\Services\AuditLogger;
use App\Services\RentalAccessCodeService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class RenterPortal extends Component
{
    private const LOGIN_LIMIT_PER_MINUTE = 5;

    private const LOGIN_DECAY_SECONDS = 60;

    public string $renter_name = '';

    public string $id_type = 'PASSPORT';

    public string $rental_code = '';

    public ?Rental $authenticatedRental = null;

    public ?string $statusMessage = null;

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
        $this->resumeSession();
    }

    public function login(RentalAccessCodeService $codes, AuditLogger $auditLogger): void
    {
        $this->resetErrorBag();
        $this->statusMessage = null;

        $validated = $this->validate([
            'renter_name' => ['required', 'string', 'max:255'],
            'id_type' => ['required', 'in:'.implode(',', $this->idTypeOptions)],
            'rental_code' => ['required', 'string', 'max:32'],
        ]);

        $normalizedRenterName = trim($validated['renter_name']);
        $normalizedIdType = strtoupper($validated['id_type']);
        $normalizedRawCode = $codes->normalizeInput($validated['rental_code']);
        $rateLimitKeys = $this->buildRateLimitKeys(
            renterName: $normalizedRenterName,
            idType: $normalizedIdType,
            normalizedRawCode: $normalizedRawCode
        );

        if ($this->isRateLimited($rateLimitKeys)) {
            $auditLogger->log(
                action: 'RENTER_LOGIN_RATE_LIMITED',
                changes: [
                    'reason' => 'too_many_attempts',
                    'limited_scopes' => $this->limitedScopes($rateLimitKeys),
                    'code_last4_input' => $normalizedRawCode !== null ? substr($normalizedRawCode, -4) : null,
                    'attempts' => $this->rateLimitAttemptSnapshot($rateLimitKeys),
                ]
            );

            $this->addError('rental_code', 'Too many login attempts. Please wait one minute and try again.');

            return;
        }

        try {
            $matchingRental = $this->attemptLogin(
                renterName: $normalizedRenterName,
                idType: $normalizedIdType,
                rentalCode: $validated['rental_code'],
                codes: $codes
            );

            $this->establishRenterSession($matchingRental);
            $this->authenticatedRental = $matchingRental;
            $this->statusMessage = 'Renter access verified successfully.';
            $this->clearRateLimitKeys($rateLimitKeys);

            $auditLogger->log(
                action: 'RENTER_LOGIN_SUCCESS',
                unit: $matchingRental->unit,
                changes: [
                    'rental_id' => $matchingRental->id,
                    'code_last4' => $matchingRental->public_code_last4,
                    'expires_at' => $matchingRental->ends_at->toIso8601String(),
                ]
            );
        } catch (ValidationException $exception) {
            $this->hitRateLimitKeys($rateLimitKeys);

            $auditLogger->log(
                action: 'RENTER_LOGIN_FAILED',
                changes: [
                    'reason' => $this->classifyFailureReason($exception),
                    'id_type' => $normalizedIdType,
                    'code_last4_input' => $normalizedRawCode !== null ? substr($normalizedRawCode, -4) : null,
                    'attempts' => $this->rateLimitAttemptSnapshot($rateLimitKeys),
                ]
            );

            throw $exception;
        }
    }

    public function logout(): void
    {
        $session = session();
        $session->forget('renter_access');
        $session->regenerate();
        $session->regenerateToken();

        $this->authenticatedRental = null;
        $this->statusMessage = 'You have been signed out of the renter portal.';
    }

    public function render()
    {
        return view('livewire.public.renter-portal');
    }

    private function attemptLogin(string $renterName, string $idType, string $rentalCode, RentalAccessCodeService $codes): Rental
    {
        $normalizedRawCode = $codes->normalizeInput($rentalCode);
        if ($normalizedRawCode === null) {
            throw ValidationException::withMessages([
                'rental_code' => 'Please enter a valid rental access code (format: XXXX-XXXX-XXXX).',
            ]);
        }

        $formattedCode = $codes->formatFromRaw($normalizedRawCode);
        $last4 = $codes->last4FromRaw($normalizedRawCode);

        $baseQuery = Rental::query()
            ->with('unit')
            ->where('status', Rental::STATUS_ACTIVE)
            ->whereRaw('LOWER(renter_name) = ?', [mb_strtolower($renterName)])
            ->where('id_type', $idType)
            ->where('public_code_last4', $last4);

        $now = CarbonImmutable::now();
        $matchingRental = (clone $baseQuery)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->orderByDesc('starts_at')
            ->get()
            ->first(fn (Rental $rental): bool => Hash::check($formattedCode, $rental->public_code_hash));

        if ($matchingRental) {
            return $matchingRental;
        }

        $expiredRental = (clone $baseQuery)
            ->where('ends_at', '<', $now)
            ->orderByDesc('ends_at')
            ->get()
            ->first(fn (Rental $rental): bool => Hash::check($formattedCode, $rental->public_code_hash));

        if ($expiredRental) {
            throw ValidationException::withMessages([
                'rental_code' => 'This rental access has ended. Please contact support for assistance.',
            ]);
        }

        $upcomingRental = (clone $baseQuery)
            ->where('starts_at', '>', $now)
            ->orderBy('starts_at')
            ->get()
            ->first(fn (Rental $rental): bool => Hash::check($formattedCode, $rental->public_code_hash));

        if ($upcomingRental) {
            throw ValidationException::withMessages([
                'rental_code' => 'Your rental access is not active yet. Please try again on or after the rental start time.',
            ]);
        }

        throw ValidationException::withMessages([
            'rental_code' => 'The renter details could not be verified. Please review and try again.',
        ]);
    }

    private function establishRenterSession(Rental $rental): void
    {
        $session = session();
        $session->regenerate();
        $session->put('renter_access', [
            'rental_id' => $rental->id,
            'expires_at' => $rental->ends_at->toIso8601String(),
        ]);
    }

    private function resumeSession(): void
    {
        $session = session('renter_access');
        if (! is_array($session) || ! isset($session['rental_id'], $session['expires_at'])) {
            return;
        }

        $expiresAt = CarbonImmutable::parse((string) $session['expires_at']);
        if (CarbonImmutable::now()->gte($expiresAt)) {
            session()->forget('renter_access');
            $this->statusMessage = 'Your renter session has expired. Please sign in again if your rental is still active.';

            return;
        }

        $rental = Rental::query()->with('unit')->find((int) $session['rental_id']);
        if (! $rental || $rental->status !== Rental::STATUS_ACTIVE) {
            session()->forget('renter_access');

            return;
        }

        $now = CarbonImmutable::now();
        if ($now->lt(CarbonImmutable::instance($rental->starts_at)) || $now->gt(CarbonImmutable::instance($rental->ends_at))) {
            session()->forget('renter_access');
            $this->statusMessage = 'Your rental access is no longer active.';

            return;
        }

        $this->authenticatedRental = $rental;
    }

    private function rateLimitKey(): string
    {
        $ip = request()->ip() ?? 'unknown';

        return 'renter-login:'.hash('sha256', $ip);
    }

    /**
     * @return array<int, string>
     */
    private function buildRateLimitKeys(string $renterName, string $idType, ?string $normalizedRawCode): array
    {
        return [
            $this->rateLimitKey(),
            $this->identityRateLimitKey($renterName, $idType, $normalizedRawCode),
        ];
    }

    private function identityRateLimitKey(string $renterName, string $idType, ?string $normalizedRawCode): string
    {
        $codeFragment = $normalizedRawCode !== null ? substr($normalizedRawCode, -4) : 'INVALID';
        $identityFingerprint = mb_strtolower(trim($renterName)).'|'.$idType.'|'.$codeFragment;

        return 'renter-login:identity:'.hash('sha256', $identityFingerprint);
    }

    /**
     * @param  array<int, string>  $rateLimitKeys
     */
    private function isRateLimited(array $rateLimitKeys): bool
    {
        foreach ($rateLimitKeys as $key) {
            if (RateLimiter::tooManyAttempts($key, self::LOGIN_LIMIT_PER_MINUTE)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $rateLimitKeys
     */
    private function hitRateLimitKeys(array $rateLimitKeys): void
    {
        foreach ($rateLimitKeys as $key) {
            RateLimiter::hit($key, self::LOGIN_DECAY_SECONDS);
        }
    }

    /**
     * @param  array<int, string>  $rateLimitKeys
     */
    private function clearRateLimitKeys(array $rateLimitKeys): void
    {
        foreach ($rateLimitKeys as $key) {
            RateLimiter::clear($key);
        }
    }

    /**
     * @param  array<int, string>  $rateLimitKeys
     * @return array<string, int>
     */
    private function rateLimitAttemptSnapshot(array $rateLimitKeys): array
    {
        $attempts = [];

        foreach ($rateLimitKeys as $key) {
            $attempts[$key] = RateLimiter::attempts($key);
        }

        return $attempts;
    }

    /**
     * @param  array<int, string>  $rateLimitKeys
     * @return array<int, string>
     */
    private function limitedScopes(array $rateLimitKeys): array
    {
        $limited = [];

        foreach ($rateLimitKeys as $key) {
            if (RateLimiter::tooManyAttempts($key, self::LOGIN_LIMIT_PER_MINUTE)) {
                $limited[] = str_contains($key, 'identity:') ? 'identity' : 'ip';
            }
        }

        return $limited;
    }

    private function classifyFailureReason(ValidationException $exception): string
    {
        $firstError = strtolower((string) data_get($exception->errors(), 'rental_code.0', 'validation_failed'));

        return match (true) {
            str_contains($firstError, 'format') => 'invalid_code_format',
            str_contains($firstError, 'ended') => 'expired_rental',
            str_contains($firstError, 'not active yet') => 'not_active_yet',
            str_contains($firstError, 'could not be verified') => 'credential_mismatch',
            default => 'validation_failed',
        };
    }
}
