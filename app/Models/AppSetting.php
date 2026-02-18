<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * @return ?string
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        try {
            $settings = Cache::rememberForever('app_settings.cache', static function (): array {
                return static::query()->pluck('value', 'key')->all();
            });
        } catch (\Throwable) {
            return $default;
        }

        return $settings[$key] ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        try {
            DB::transaction(function () use ($key, $value): void {
                static::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => $value],
                );
            });
        } finally {
            static::forgetCache();
        }
    }

    public static function forgetCache(): void
    {
        Cache::forget('app_settings.cache');
    }
}

