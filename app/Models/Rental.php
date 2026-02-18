<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rental extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'unit_id',
        'renter_name',
        'contact_number',
        'id_type',
        'id_last4',
        'public_code_hash',
        'public_code_last4',
        'status',
        'starts_at',
        'ends_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function renterSessions(): HasMany
    {
        return $this->hasMany(RenterSession::class);
    }

    public function maintenanceTickets(): HasMany
    {
        return $this->hasMany(MaintenanceTicket::class);
    }

    public function isActiveNow(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        $now = CarbonImmutable::now();

        return $now->betweenIncluded(
            CarbonImmutable::instance($this->starts_at),
            CarbonImmutable::instance($this->ends_at)
        );
    }

    public function isExpired(): bool
    {
        return CarbonImmutable::now()->gt(CarbonImmutable::instance($this->ends_at));
    }
}
