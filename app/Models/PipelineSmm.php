<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PipelineSmm extends Model
{
    protected $table = 'central_pipeline_smm';

    protected $fillable = [
        'nome', 'citta', 'piattaforma', 'profilo',
        'ristoranti', 'stato', 'fee', 'clienti',
        'data_contatto', 'canale', 'note',
    ];

    protected $casts = [
        'data_contatto' => 'date',
        'ristoranti'    => 'integer',
        'fee'           => 'integer',
        'clienti'       => 'integer',
    ];

    public function guadagnoTotale(): int
    {
        return ($this->clienti ?? 0) * ($this->fee ?? 60);
    }
}
