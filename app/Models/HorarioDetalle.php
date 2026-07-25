<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioDetalle extends Model
{
    protected $table = 'HORARIO_DETALLE';
    protected $primaryKey = 'ID_HORARIODETALLE';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'ES_DIA_DESCANSO' => 'boolean',
    ];

    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horarios::class, 'ID_HORARIO', 'ID_HORARIO');
    }
}
