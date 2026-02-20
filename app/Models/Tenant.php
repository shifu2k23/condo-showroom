<?php

namespace App\Models;

use App\Support\Tenancy\TenantCategoryDefaults;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_disabled',
        'trial_ends_at',
    ];

    protected $casts = [
        'is_disabled' => 'boolean',
        'trial_ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Tenant $tenant): void {
            if ($tenant->slug === '' || $tenant->slug === null) {
                $tenant->slug = Str::slug($tenant->name);
            }
        });

        static::created(function (Tenant $tenant): void {
            app(TenantCategoryDefaults::class)->seedForTenant($tenant);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function unitImages(): HasMany
    {
        return $this->hasMany(UnitImage::class);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    public function viewingRequests(): HasMany
    {
        return $this->hasMany(ViewingRequest::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
