<?php

use App\Models\Unit;
use App\Models\User;
use App\Services\AuditLogger;

test('audit logger redacts sensitive keys before persistence', function () {
    $admin = User::factory()->admin()->create();
    $unit = Unit::factory()->create(['created_by' => $admin->id]);

    $this->actingAs($admin);

    $log = app(AuditLogger::class)->log(
        action: 'SECURITY_TEST',
        unit: $unit,
        changes: [
            'safe' => 'value',
            'api_token' => 'plain-token',
            'rental_code' => 'ABCD-EFGH-JKLM',
            'public_code_hash' => 'hashed-value',
            'nested' => [
                'password' => 'plain-password',
                'authorization_header' => 'Bearer abc123',
                'id_last4' => '1234',
            ],
        ]
    );

    expect($log->changes['safe'])->toBe('value');
    expect($log->changes['api_token'])->toBe('[REDACTED]');
    expect($log->changes['rental_code'])->toBe('[REDACTED]');
    expect($log->changes['public_code_hash'])->toBe('[REDACTED]');
    expect($log->changes['nested']['password'])->toBe('[REDACTED]');
    expect($log->changes['nested']['authorization_header'])->toBe('[REDACTED]');
    expect($log->changes['nested']['id_last4'])->toBe('[REDACTED]');
});
