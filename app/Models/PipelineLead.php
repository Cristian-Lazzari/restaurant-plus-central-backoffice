<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PipelineLead extends Model
{
    protected $table = 'central_pipeline_leads';

    protected $fillable = [
        'nome', 'ristorante', 'citta', 'telefono', 'email',
        'fonte', 'smm_ref', 'stato', 'priorita', 'pacchetto',
        'valore', 'data_contatto', 'followup_date', 'nextstep', 'note', 'tag',
    ];

    protected $casts = [
        'data_contatto'  => 'date',
        'followup_date'  => 'date',
        'valore'         => 'integer',
    ];

    public function isOverdue(): bool
    {
        return $this->followup_date
            && $this->followup_date->isPast()
            && ! in_array($this->stato, ['chiuso', 'perso']);
    }
}
