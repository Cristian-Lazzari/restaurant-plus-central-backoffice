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
        'sort_order',
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
        // Campi CRM/pipeline (is_prospect = true → non è ancora cliente connesso)
        'is_prospect',
        'citta',
        'telefono',
        'email',
        'fonte',
        'smm_ref',
        'stato',
        'priorita',
        'valore',
        'sconto',
        'tipo_sconto',
        'data_contatto',
        'followup_date',
        'nextstep',
        'tag',
    ];

    protected $hidden = [
        'token',
    ];

    protected $casts = [
        'token' => 'encrypted',
        'sort_order' => 'integer',
        'active' => 'boolean',
        'pack' => 'integer',
        'consecutive_failures' => 'integer',
        'retention_days' => 'integer',
        'last_sync_at' => 'datetime',
        'last_success_at' => 'datetime',
        'last_error_at' => 'datetime',
        'is_prospect' => 'boolean',
        'data_contatto' => 'date',
        'followup_date' => 'date',
        'valore' => 'integer',
        'sconto' => 'integer',
    ];

    // ─── Scopes ──────────────────────────────────────────────────────────────────

    /**
     * Filtra solo i siti realmente connessi (clienti con dashboard attiva).
     * Usato ovunque nella dashboard per escludere i prospect della pipeline CRM.
     */
    public function scopeConnected($query)
    {
        return $query->where('is_prospect', false);
    }

    // ─── Accessor / metodi di business ───────────────────────────────────────────

    /**
     * True se il follow-up è scaduto e il lead non è ancora chiuso/perso.
     */
    public function isOverdue(): bool
    {
        return $this->followup_date
            && $this->followup_date->isPast()
            && ! in_array($this->stato, ['chiuso', 'perso']);
    }

    // ─── Relazioni ───────────────────────────────────────────────────────────────

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
