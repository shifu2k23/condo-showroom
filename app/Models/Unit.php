<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use BelongsToTenant, HasFactory, HasUlids, SoftDeletes;

    public const STATUS_AVAILABLE = 'AVAILABLE';

    public const STATUS_UNAVAILABLE = 'UNAVAILABLE';

    public const DISPLAY_NIGHT = 'NIGHT';

    public const DISPLAY_MONTH = 'MONTH';

    public const ESTIMATOR_HYBRID = 'HYBRID';

    public const ESTIMATOR_NIGHTLY_ONLY = 'NIGHTLY_ONLY';

    public const ESTIMATOR_MONTHLY_ONLY = 'MONTHLY_ONLY';

    protected $fillable = [
        'tenant_id',
        'public_id',
        'category_id',
        'name',
        'slug',
        'location',
        'latitude',
        'longitude',
        'address_text',
        'description',
        'ai_description_draft',
        'ai_description_meta',
        'ai_description_generated_at',
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
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'ai_description_meta' => 'array',
        'ai_description_generated_at' => 'datetime',
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

    public function maintenanceTickets(): HasMany
    {
        return $this->hasMany(MaintenanceTicket::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function googleMapsUrl(): string
    {
        $coords = $this->formattedCoordinates();

        if ($coords === null) {
            return '';
        }

        return "https://www.google.com/maps?q={$coords[0]},{$coords[1]}";
    }

    public function googleDirectionsUrl(): string
    {
        $coords = $this->formattedCoordinates();

        if ($coords === null) {
            return '';
        }

        return "https://www.google.com/maps/dir/?api=1&destination={$coords[0]},{$coords[1]}";
    }

    /**
     * @return array{0:string,1:string}|null
     */
    private function formattedCoordinates(): ?array
    {
        if (! $this->hasLocation()) {
            return null;
        }

        return [
            number_format((float) $this->latitude, 7, '.', ''),
            number_format((float) $this->longitude, 7, '.', ''),
        ];
    }
}
