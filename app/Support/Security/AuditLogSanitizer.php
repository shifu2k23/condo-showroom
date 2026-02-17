<?php

namespace App\Support\Security;

class AuditLogSanitizer
{
    private const REDACTED = '[REDACTED]';

    /**
     * @var array<int, string>
     */
    private array $sensitiveKeyFragments = [
        'password',
        'token',
        'secret',
        'authorization',
        'cookie',
        'api_key',
        'apikey',
        'private_key',
        'refresh_token',
        'access_token',
        'two_factor',
        'otp',
        'rental_code',
        'public_code',
        'id_last4',
        'id_number',
    ];

    public function sanitize(?array $changes): ?array
    {
        if ($changes === null) {
            return null;
        }

        return $this->sanitizeValue($changes);
    }

    private function sanitizeValue(mixed $value, ?string $key = null): mixed
    {
        if ($this->isSensitiveKey($key)) {
            return self::REDACTED;
        }

        if (! is_array($value)) {
            return $value;
        }

        $sanitized = [];

        foreach ($value as $nestedKey => $nestedValue) {
            $nestedKeyString = is_string($nestedKey) ? $nestedKey : null;
            $sanitized[$nestedKey] = $this->sanitizeValue($nestedValue, $nestedKeyString);
        }

        return $sanitized;
    }

    private function isSensitiveKey(?string $key): bool
    {
        if ($key === null) {
            return false;
        }

        $normalized = strtolower($key);

        foreach ($this->sensitiveKeyFragments as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
