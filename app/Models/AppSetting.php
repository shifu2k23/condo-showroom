<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AppSetting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'key',
        'value',
    ];

    /**
     * @return ?string
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $tenantId = static::resolveCurrentTenantId();
        if ($tenantId === null) {
            return $default;
        }

        try {
            $settings = Cache::rememberForever(static::cacheKey($tenantId), static function () use ($tenantId): array {
                return static::query()
                    ->withoutGlobalScope('tenant')
                    ->where('tenant_id', $tenantId)
                    ->pluck('value', 'key')
                    ->all();
            });
        } catch (\Throwable) {
            return $default;
        }

        return $settings[$key] ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        $tenantId = static::resolveCurrentTenantId();
        if ($tenantId === null) {
            return;
        }

        try {
            DB::transaction(function () use ($tenantId, $key, $value): void {
                static::query()
                    ->withoutGlobalScope('tenant')
                    ->updateOrCreate(
                    ['tenant_id' => $tenantId, 'key' => $key],
                    ['value' => $value],
                );
            });
        } finally {
            static::forgetCache($tenantId);
        }
    }

    public static function forgetCache(?int $tenantId = null): void
    {
        $tenantId ??= static::resolveCurrentTenantId();
        if ($tenantId === null) {
            return;
        }

        Cache::forget(static::cacheKey($tenantId));
    }

    private static function cacheKey(int $tenantId): string
    {
        return 'app_settings.cache.tenant.'.$tenantId;
    }

    private static function resolveCurrentTenantId(): ?int
    {
        return app(TenantManager::class)->currentId();
    }
}
