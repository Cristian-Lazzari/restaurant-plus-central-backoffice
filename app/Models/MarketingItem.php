<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingItem extends Model
{
    public const TYPES = ['post', 'storia', 'video', 'promo', 'campagna', 'automazione', 'modello'];

    public const SLOTS = ['mattina', 'pomeriggio'];

    protected $fillable = [
        'marketing_plan_id',
        'type',
        'code',
        'title',
        'description',
        'payload',
        'week',
        'day_index',
        'slot',
        'completed',
        'completed_date',
        'notes',
    ];

    protected $casts = [
        'payload' => 'array',
        'completed' => 'boolean',
        'completed_date' => 'date',
        'week' => 'integer',
        'day_index' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MarketingPlan::class, 'marketing_plan_id');
    }

    /**
     * Data pianificata derivata dalla posizione nel calendario
     * (start_date del piano + settimane + giorno).
     */
    public function scheduledDate(): ?\Carbon\Carbon
    {
        $start = $this->plan?->start_date;

        if (! $start || $this->week === null || $this->day_index === null) {
            return null;
        }

        return $start->copy()->addDays(($this->week - 1) * 7 + $this->day_index);
    }
}
