<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Planilla extends Model
{
    protected $table = 'PLANILLA';
    protected $primaryKey = 'ID_PLANILLA';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'FECHAPAGO' => 'datetime',
        'ESACTIVA' => 'boolean',
        'CERRADA' => 'boolean',
        'ANULADA' => 'boolean',
        'RECALCULADA' => 'boolean',
        'CONTABILIZADA' => 'boolean',
        'FECHA_CREACION' => 'datetime',
        'FECHAULTIMAMODIFICACION' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'ID_EMPRESA', 'ID_EMPRESA');
    }

    public function tipoPlanilla(): BelongsTo
    {
        return $this->belongsTo(TipoPlanilla::class, 'ID_TIPOPLANILLA', 'ID_TIPOPLANILLA');
    }

    public function periodoLaboral(): BelongsTo
    {
        return $this->belongsTo(PeriodoLaboral::class, 'ID_PERIODO', 'ID_PERIODO');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePlanilla::class, 'ID_PLANILLA', 'ID_PLANILLA');
    }
}
