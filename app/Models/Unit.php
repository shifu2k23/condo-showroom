<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const STATUS_AVAILABLE = 'AVAILABLE';

    public const STATUS_UNAVAILABLE = 'UNAVAILABLE';

    public const DISPLAY_NIGHT = 'NIGHT';

    public const DISPLAY_MONTH = 'MONTH';

    public const ESTIMATOR_HYBRID = 'HYBRID';

    public const ESTIMATOR_NIGHTLY_ONLY = 'NIGHTLY_ONLY';

    public const ESTIMATOR_MONTHLY_ONLY = 'MONTHLY_ONLY';

    protected $fillable = [
        'public_id',
        'category_id',
        'name',
        'slug',
        'location',
        'description',
        'status',
        'nightly_price_php',
        'monthly_price_php',
        'price_display_mode',
        'estimator_mode',
        'allow_estimator',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'nightly_price_php' => 'integer',
        'monthly_price_php' => 'integer',
        'allow_estimator' => 'boolean',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if ($field !== null) {
            return $this->where($field, $value)->firstOrFail();
        }

        return $this->where('slug', $value)
            ->orWhere('public_id', $value)
            ->when(is_numeric($value), fn ($query) => $query->orWhereKey($value))
            ->firstOrFail();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(UnitImage::class)->orderBy('sort_order');
    }

    public function viewingRequests(): HasMany
    {
        return $this->hasMany(ViewingRequest::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
