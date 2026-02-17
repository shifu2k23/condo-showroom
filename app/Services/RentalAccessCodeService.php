<?php

namespace App\Services;

use InvalidArgumentException;

class RentalAccessCodeService
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const RAW_LENGTH = 12;

    public function generate(): string
    {
        $raw = '';
        $alphabetLength = strlen(self::ALPHABET);

        for ($index = 0; $index < self::RAW_LENGTH; $index++) {
            $raw .= self::ALPHABET[random_int(0, $alphabetLength - 1)];
        }

        return $this->formatFromRaw($raw);
    }

    public function normalizeInput(string $value): ?string
    {
        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $value) ?? '');

        if (strlen($normalized) !== self::RAW_LENGTH) {
            return null;
        }

        if (strspn($normalized, self::ALPHABET) !== self::RAW_LENGTH) {
            return null;
        }

        return $normalized;
    }

    public function formatFromRaw(string $raw): string
    {
        $normalized = strtoupper($raw);

        if (strlen($normalized) !== self::RAW_LENGTH) {
            throw new InvalidArgumentException('Raw access code must be exactly 12 characters.');
        }

        return implode('-', str_split($normalized, 4));
    }

    public function last4FromRaw(string $raw): string
    {
        return substr($raw, -4);
    }
}
