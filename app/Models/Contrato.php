<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contrato extends Model
{
    protected $table = 'CONTRATO';
    protected $primaryKey = 'ID_CONTRATO';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'FECHA_INICIO' => 'date',
        'FECHA_FIN' => 'date',
        'SIN_FECHA_DEFINIDA' => 'boolean',
        'SALARIO' => 'decimal:2',
        'CAMPOS_EXTRA' => 'array',
        'ESACTIVO' => 'boolean',
        'FECHA_CREACION' => 'datetime',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'ID_EMPLEADO', 'ID_EMPLEADO');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'ID_EMPRESA', 'ID_EMPRESA');
    }

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(PlantillaContrato::class, 'ID_PLANTILLA', 'ID_PLANTILLA');
    }
}
