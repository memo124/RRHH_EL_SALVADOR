<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestamoAbono extends Model
{
    protected $table = 'PRESTAMO_ABONO';
    protected $primaryKey = 'ID_PRESTAMOABONO';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'FECHAABONO' => 'datetime',
        'MONTOABONADO' => 'decimal:2',
        'FUERA_PLANILLA' => 'boolean',
    ];

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamos::class, 'ID_PRESTAMO', 'ID_PRESTAMO');
    }

    public function detallePlanilla(): BelongsTo
    {
        return $this->belongsTo(DetallePlanilla::class, 'ID_DETALLEPLANILLA', 'ID_DETALLEPLANILLA');
    }
}
