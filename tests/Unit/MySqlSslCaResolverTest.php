<?php

use App\Support\Database\MySqlSslCaResolver;

test('returns configured path when it is readable', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'ca_');
    file_put_contents($tempFile, 'dummy-ca');

    $resolved = MySqlSslCaResolver::resolve($tempFile, []);

    expect($resolved)->toBe($tempFile);

    @unlink($tempFile);
});

test('falls back to first readable candidate when configured path is invalid', function () {
    $fallbackFile = tempnam(sys_get_temp_dir(), 'ca_fallback_');
    file_put_contents($fallbackFile, 'dummy-ca');

    $resolved = MySqlSslCaResolver::resolve('/definitely/missing/ca.pem', [
        '/another/missing/path.pem',
        $fallbackFile,
    ], []);

    expect($resolved)->toBe($fallbackFile);

    @unlink($fallbackFile);
});

test('uses runtime candidates before fallback candidates', function () {
    $runtimeFile = tempnam(sys_get_temp_dir(), 'ca_runtime_');
    file_put_contents($runtimeFile, 'dummy-ca');

    $fallbackFile = tempnam(sys_get_temp_dir(), 'ca_fallback_');
    file_put_contents($fallbackFile, 'dummy-ca');

    $resolved = MySqlSslCaResolver::resolve('/missing/ca.pem', [
        $fallbackFile,
    ], [
        '/missing/runtime-ca.pem',
        $runtimeFile,
    ]);

    expect($resolved)->toBe($runtimeFile);

    @unlink($runtimeFile);
    @unlink($fallbackFile);
});

test('returns null when no candidates are readable', function () {
    $resolved = MySqlSslCaResolver::resolve('/missing/ca.pem', [
        '/missing/a.pem',
        '/missing/b.pem',
    ], []);

    expect($resolved)->toBeNull();
});
