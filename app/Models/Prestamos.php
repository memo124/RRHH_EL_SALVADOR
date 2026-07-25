<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prestamos extends Model
{
    protected $table = 'PRESTAMOS';
    protected $primaryKey = 'ID_PRESTAMO';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'MONTOPRESTAMO' => 'decimal:2',
        'CUOTA' => 'decimal:2',
        'NUMCUOTAS' => 'integer',
        'SALDO_ACTUAL' => 'decimal:2',
        'FECHAINICIO' => 'datetime',
        'FECHAFINALIZACION' => 'datetime',
        'PRESTAMOESTADO' => 'boolean',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'ID_EMPLEADO', 'ID_EMPLEADO');
    }

    public function tipoDescuento(): BelongsTo
    {
        return $this->belongsTo(TipoDescuento::class, 'ID_TIPODESCUENTO', 'ID_TIPODESCUENTO');
    }

    public function tipoPrestamo(): BelongsTo
    {
        return $this->belongsTo(TipoPrestamo::class, 'ID_TIPOPRESTAMO', 'ID_TIPOPRESTAMO');
    }

    public function abonos(): HasMany
    {
        return $this->hasMany(PrestamoAbono::class, 'ID_PRESTAMO', 'ID_PRESTAMO');
    }
}
