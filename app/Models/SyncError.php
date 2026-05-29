<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncError extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'code',
        'http_status_code',
        'message',
        'context',
        'consecutive_failures',
        'occurred_at',
    ];

    protected $casts = [
        'http_status_code' => 'integer',
        'context' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
