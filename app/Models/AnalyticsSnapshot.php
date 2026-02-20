<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsSnapshot extends Model
{
    use BelongsToTenant, HasFactory;

    public const PERIOD_DAY = 'DAY';

    public const PERIOD_WEEK = 'WEEK';

    public const PERIOD_MONTH = 'MONTH';

    protected $fillable = [
        'tenant_id',
        'period_type',
        'period_start',
        'period_end',
        'metrics',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'metrics' => 'array',
    ];
}
