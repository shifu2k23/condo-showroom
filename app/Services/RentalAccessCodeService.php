<?php

namespace App\Services;

use InvalidArgumentException;

class RentalAccessCodeService
{
    private const DIGITS = '0123456789';

    private const RAW_LENGTH = 6;

    public function generate(): string
    {
        $raw = '';
        $digitsLength = strlen(self::DIGITS);

        for ($index = 0; $index < self::RAW_LENGTH; $index++) {
            $raw .= self::DIGITS[random_int(0, $digitsLength - 1)];
        }

        return $this->formatFromRaw($raw);
    }

    public function normalizeInput(string $value): ?string
    {
        $normalized = preg_replace('/\D+/', '', $value) ?? '';

        if (strlen($normalized) !== self::RAW_LENGTH) {
            return null;
        }

        if (strspn($normalized, self::DIGITS) !== self::RAW_LENGTH) {
            return null;
        }

        return $normalized;
    }

    public function formatFromRaw(string $raw): string
    {
        $normalized = preg_replace('/\D+/', '', $raw) ?? '';

        if (strlen($normalized) !== self::RAW_LENGTH) {
            throw new InvalidArgumentException('Raw access code must be exactly 6 digits.');
        }

        return $normalized;
    }

    public function last4FromRaw(string $raw): string
    {
        return substr($raw, -4);
    }
}
