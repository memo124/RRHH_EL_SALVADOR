<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DescuentoEmpleado extends Model
{
    protected $table = 'DESCUENTO_EMPLEADO';
    protected $primaryKey = 'ID_DESCUENTOEMPLEADO';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'MONTO' => 'decimal:2',
        'PORCENTAJE' => 'decimal:2',
        'ES_PORCENTAJE' => 'boolean',
        'FECHAINICIO' => 'datetime',
        'FECHAFIN' => 'datetime',
        'ESACTIVO' => 'boolean',
        'ES_RECURRENTE' => 'boolean',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'ID_EMPLEADO', 'ID_EMPLEADO');
    }

    public function tipoDescuento(): BelongsTo
    {
        return $this->belongsTo(TipoDescuento::class, 'ID_TIPODESCUENTO', 'ID_TIPODESCUENTO');
    }
}
