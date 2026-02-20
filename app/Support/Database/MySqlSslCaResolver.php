<?php

namespace App\Support\Database;

class MySqlSslCaResolver
{
    /**
     * Resolve the first readable CA bundle path.
     *
     * @param  array<int, string>|null  $fallbackCandidates
     * @param  array<int, string>|null  $runtimeCandidates
     */
    public static function resolve(
        ?string $configuredPath = null,
        ?array $fallbackCandidates = null,
        ?array $runtimeCandidates = null
    ): ?string
    {
        $configuredPath = is_string($configuredPath) ? trim($configuredPath) : '';

        $candidates = [];
        if ($configuredPath !== '') {
            $candidates[] = $configuredPath;
        }

        $runtimeCandidates ??= [
            (string) ini_get('openssl.cafile'),
            (string) ini_get('curl.cainfo'),
        ];

        foreach ($runtimeCandidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }

            $candidates[] = $candidate;
        }

        $fallbackCandidates ??= [
            '/etc/ssl/certs/ca-certificates.crt',
            '/etc/ssl/cert.pem',
            '/etc/pki/tls/certs/ca-bundle.crt',
            '/etc/ssl/ca-bundle.pem',
        ];

        foreach ($fallbackCandidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }

            $candidates[] = $candidate;
        }

        foreach (array_unique($candidates) as $candidate) {
            if (is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
