<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingPlan extends Model
{
    protected $fillable = [
        'site_id',
        'objective',
        'timeline_label',
        'weeks',
        'start_date',
        'social_status',
        'photos_needed',
        'reels_needed',
        'kpis',
    ];

    protected $casts = [
        'start_date' => 'date',
        'social_status' => 'array',
        'kpis' => 'array',
        'weeks' => 'integer',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MarketingItem::class);
    }
}
