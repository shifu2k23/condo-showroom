<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\RentalAccessCodeService;
use App\Services\RenterAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class RenterAccessController extends Controller
{
    public function store(
        Request $request,
        RenterAccessService $renterAccess,
        RentalAccessCodeService $codes,
        AuditLogger $auditLogger
    ): RedirectResponse {
        $validated = $request->validate([
            'renter_name' => ['required', 'string', 'max:255'],
            'id_type' => ['required', 'in:PASSPORT,DRIVER_LICENSE,NATIONAL_ID,OTHER'],
            'rental_code' => ['required', 'string', 'max:32'],
        ]);

        $normalizedRenterName = trim($validated['renter_name']);
        $normalizedIdType = strtoupper($validated['id_type']);
        $normalizedRawCode = $codes->normalizeInput($validated['rental_code']);

        $rateLimitKeys = $this->buildRateLimitKeys($request, $normalizedRenterName, $normalizedIdType, $normalizedRawCode);
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

            return back()
                ->withErrors(['rental_code' => 'Too many login attempts. Please wait one minute and try again.'])
                ->withInput($request->except('rental_code'));
        }

        try {
            $rental = $renterAccess->verifyCredentials(
                renterName: $normalizedRenterName,
                idType: $normalizedIdType,
                rentalCode: $validated['rental_code']
            );

            $issuedSession = $renterAccess->issueSession($rental);
            $renterAccess->establishBrowserSession(
                rental: $rental,
                sessionModel: $issuedSession['model'],
                token: $issuedSession['token'],
                expiresAt: $issuedSession['expires_at']
            );

            $this->clearRateLimitKeys($rateLimitKeys);

            $auditLogger->log(
                action: 'RENTER_LOGIN_SUCCESS',
                unit: $rental->unit,
                changes: [
                    'rental_id' => $rental->id,
                    'code_last4' => $rental->public_code_last4,
                    'renter_session_id' => $issuedSession['model']->id,
                    'expires_at' => $issuedSession['expires_at']->toIso8601String(),
                ]
            );

            return redirect()->route('renter.dashboard');
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

    /**
     * @return array<int, string>
     */
    private function buildRateLimitKeys(Request $request, string $renterName, string $idType, ?string $normalizedRawCode): array
    {
        return [
            $this->ipRateLimitKey($request),
            $this->identityRateLimitKey($renterName, $idType, $normalizedRawCode),
        ];
    }

    private function ipRateLimitKey(Request $request): string
    {
        $ip = $request->ip() ?? 'unknown';

        return 'renter-login:'.hash('sha256', $ip);
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
            if (RateLimiter::tooManyAttempts($key, 5)) {
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
            RateLimiter::hit($key, 60);
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
            if (RateLimiter::tooManyAttempts($key, 5)) {
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
            str_contains($firstError, 'ended') || str_contains($firstError, 'no active rental') => 'expired_rental',
            str_contains($firstError, 'not active yet') => 'not_active_yet',
            str_contains($firstError, 'could not be verified') => 'credential_mismatch',
            default => 'validation_failed',
        };
    }
}
