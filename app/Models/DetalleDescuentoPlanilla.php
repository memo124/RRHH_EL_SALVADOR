<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleDescuentoPlanilla extends Model
{
    protected $table = 'DETALLE_DESCUENTO_PLANILLA';
    protected $primaryKey = 'ID_DETALLEDESCPLANILLA';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'MONTO' => 'decimal:2',
    ];

    public function detallePlanilla(): BelongsTo
    {
        return $this->belongsTo(DetallePlanilla::class, 'ID_DETALLEPLANILLA', 'ID_DETALLEPLANILLA');
    }

    public function tipoDescuento(): BelongsTo
    {
        return $this->belongsTo(TipoDescuento::class, 'ID_TIPODESCUENTO', 'ID_TIPODESCUENTO');
    }
}
