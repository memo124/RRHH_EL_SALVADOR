<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtroIngreso extends Model
{
    protected $table = 'OTRO_INGRESO';
    protected $primaryKey = 'ID_OTROINGRESO';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'MONTOINGRESO' => 'decimal:2',
        'FECHAINICIO' => 'datetime',
        'FECHAFIN' => 'datetime',
        'ESACTIVO' => 'boolean',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'ID_EMPLEADO', 'ID_EMPLEADO');
    }

    public function tipoIngreso(): BelongsTo
    {
        return $this->belongsTo(TipoIngreso::class, 'ID_TIPOINGRESO', 'ID_TIPOINGRESO');
    }
}
