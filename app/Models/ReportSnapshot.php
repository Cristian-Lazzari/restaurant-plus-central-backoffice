<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'period_from',
        'period_to',
        'api_version',
        'revenue_unit',
        'payload',
        'data_warnings',
        'http_status_code',
        'response_time_ms',
        'has_warnings',
        'orders_total',
        'orders_revenue',
        'reservations_total',
        'reservations_covers',
        'orders_today',
        'reservations_today',
        'orders_last_7_days',
        'reservations_last_7_days',
        'orders_last_30_days',
        'reservations_last_30_days',
        'orders_current_month',
        'reservations_current_month',
        'fetched_at',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'payload' => 'array',
        'data_warnings' => 'array',
        'http_status_code' => 'integer',
        'response_time_ms' => 'integer',
        'has_warnings' => 'boolean',
        'orders_total' => 'integer',
        'orders_revenue' => 'integer',
        'reservations_total' => 'integer',
        'reservations_covers' => 'integer',
        'orders_today' => 'integer',
        'reservations_today' => 'integer',
        'orders_last_7_days' => 'integer',
        'reservations_last_7_days' => 'integer',
        'orders_last_30_days' => 'integer',
        'reservations_last_30_days' => 'integer',
        'orders_current_month' => 'integer',
        'reservations_current_month' => 'integer',
        'fetched_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
