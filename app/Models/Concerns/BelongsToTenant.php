<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Support\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $manager = app(TenantManager::class);
            if ($manager->shouldBypassTenantScope()) {
                return;
            }

            $tenantId = $manager->currentId();
            if ($tenantId === null) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where($builder->qualifyColumn('tenant_id'), $tenantId);
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('tenant_id') !== null) {
                return;
            }

            $manager = app(TenantManager::class);
            $tenantId = $manager->currentId();

            if ($tenantId === null && app()->runningInConsole()) {
                $firstTenantId = Tenant::query()->value('id');
                $tenantId = is_numeric($firstTenantId) ? (int) $firstTenantId : null;
            }

            if ($tenantId !== null) {
                $model->setAttribute('tenant_id', $tenantId);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForTenant(Builder $query, Tenant|int $tenant): Builder
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->getKey() : $tenant;

        return $query
            ->withoutGlobalScope('tenant')
            ->where($query->qualifyColumn('tenant_id'), $tenantId);
    }
}

