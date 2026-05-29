<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'token',
        'active',
        'pack',
        'consecutive_failures',
        'notes',
        'retention_days',
        'last_sync_at',
        'last_success_at',
        'last_error_at',
    ];

    protected $hidden = [
        'token',
    ];

    protected $casts = [
        'token' => 'encrypted',
        'active' => 'boolean',
        'pack' => 'integer',
        'consecutive_failures' => 'integer',
        'retention_days' => 'integer',
        'last_sync_at' => 'datetime',
        'last_success_at' => 'datetime',
        'last_error_at' => 'datetime',
    ];

    public function reportSnapshots(): HasMany
    {
        return $this->hasMany(ReportSnapshot::class);
    }

    public function latestSnapshot(): HasOne
    {
        return $this->hasOne(ReportSnapshot::class)->latestOfMany('fetched_at');
    }

    public function syncErrors(): HasMany
    {
        return $this->hasMany(SyncError::class);
    }

    public function latestError(): HasOne
    {
        return $this->hasOne(SyncError::class)->latestOfMany('occurred_at');
    }
}
