<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsistenciaDiaria extends Model
{
    protected $table = 'ASISTENCIA_DIARIA';
    protected $primaryKey = 'ID_ASISTENCIA';
    public $incrementing = true;
    public $timestamps = false;

    protected $casts = [
        'FECHA' => 'date',
        'HORA_ENTRADA_REAL' => 'datetime',
        'HORA_SALIDA_REAL' => 'datetime',
        'MINUTOS_LLEGADA_TARDE' => 'integer',
        'MINUTOS_SALIDA_TEMPRANO' => 'integer',
        'HORAS_TRABAJADAS' => 'decimal:2',
        'HORAS_EXTRAS_DIURNAS' => 'decimal:2',
        'HORAS_EXTRAS_NOCTURNAS' => 'decimal:2',
        'ES_INASISTENCIA' => 'boolean',
        'ES_INCAPACIDAD' => 'boolean',
        'ES_PERMISO' => 'boolean',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'ID_EMPLEADO', 'ID_EMPLEADO');
    }

    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horarios::class, 'ID_HORARIO', 'ID_HORARIO');
    }
}
