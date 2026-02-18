<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceTicket extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'OPEN';

    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';

    public const STATUS_RESOLVED = 'RESOLVED';

    public const STATUS_CLOSED = 'CLOSED';

    public const CATEGORY_CLEANING = 'CLEANING';

    public const CATEGORY_PLUMBING = 'PLUMBING';

    public const CATEGORY_ELECTRICAL = 'ELECTRICAL';

    public const CATEGORY_OTHER = 'OTHER';

    protected $fillable = [
        'rental_id',
        'unit_id',
        'status',
        'category',
        'subject',
        'description',
        'attachment_path',
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
