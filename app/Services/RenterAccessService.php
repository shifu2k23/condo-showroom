<?php

namespace App\Services;

use App\Models\Rental;
use App\Models\RenterSession;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RenterAccessService
{
    public const SESSION_KEY = 'renter_access';

    public const EXPIRED_ACCESS_MESSAGE = 'Access unavailable. Our records show there is no active rental under these details. If you believe this is an error, please contact the front desk.';

    public function __construct(
        private readonly RentalAccessCodeService $codes
    ) {}

    public function verifyCredentials(string $renterName, string $idType, string $rentalCode): Rental
    {
        $normalizedRawCode = $this->codes->normalizeInput($rentalCode);
        if ($normalizedRawCode === null) {
            throw ValidationException::withMessages([
                'rental_code' => 'Please enter a valid rental access code (6 digits).',
            ]);
        }

        $formattedCode = $this->codes->formatFromRaw($normalizedRawCode);
        $last4 = $this->codes->last4FromRaw($normalizedRawCode);
        $normalizedName = trim($renterName);
        $normalizedIdType = strtoupper($idType);

        $baseQuery = Rental::query()
            ->with('unit')
            ->where('status', Rental::STATUS_ACTIVE)
            ->whereRaw('LOWER(renter_name) = ?', [mb_strtolower($normalizedName)])
            ->where('id_type', $normalizedIdType)
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
                'rental_code' => self::EXPIRED_ACCESS_MESSAGE,
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

    /**
     * @return array{model:RenterSession, token:string, expires_at:CarbonImmutable}
     */
    public function issueSession(Rental $rental): array
    {
        $token = Str::random(80);
        $expiresAt = CarbonImmutable::instance($rental->ends_at);

        $sessionModel = RenterSession::query()->create([
            'rental_id' => $rental->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expiresAt,
            'last_used_at' => now(),
        ]);

        return [
            'model' => $sessionModel,
            'token' => $token,
            'expires_at' => $expiresAt,
        ];
    }

    public function establishBrowserSession(Rental $rental, RenterSession $sessionModel, string $token, CarbonImmutable $expiresAt): void
    {
        $session = session();
        $session->regenerate();
        $session->put(self::SESSION_KEY, [
            'rental_id' => $rental->id,
            'renter_session_id' => $sessionModel->id,
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    public function clearBrowserSession(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function resolveRentalFromBrowserSession(bool $allowExpiredRental = false): ?Rental
    {
        $sessionData = session(self::SESSION_KEY);
        if (! is_array($sessionData)) {
            return null;
        }

        $rentalId = data_get($sessionData, 'rental_id');
        $renterSessionId = data_get($sessionData, 'renter_session_id');
        $plainToken = data_get($sessionData, 'token');

        $rentalId = is_numeric($rentalId) ? (int) $rentalId : null;
        $renterSessionId = is_numeric($renterSessionId) ? (int) $renterSessionId : null;

        if ($rentalId === null || $rentalId <= 0 || $renterSessionId === null || $renterSessionId <= 0 || ! is_string($plainToken) || $plainToken === '') {
            return null;
        }

        $renterSession = RenterSession::query()->find($renterSessionId);
        if (! $renterSession || $renterSession->rental_id !== $rentalId) {
            return null;
        }

        $tokenHash = hash('sha256', $plainToken);
        if (! hash_equals($renterSession->token_hash, $tokenHash)) {
            return null;
        }

        $now = CarbonImmutable::now();
        if ($now->gte(CarbonImmutable::instance($renterSession->expires_at))) {
            return null;
        }

        $rental = Rental::query()->with(['unit.category'])->find($rentalId);
        if (! $rental || $rental->status !== Rental::STATUS_ACTIVE) {
            return null;
        }

        if ($now->lt(CarbonImmutable::instance($rental->starts_at))) {
            return null;
        }

        if (! $allowExpiredRental && $now->gt(CarbonImmutable::instance($rental->ends_at))) {
            return null;
        }

        $renterSession->forceFill(['last_used_at' => now()])->save();

        return $rental;
    }
}
