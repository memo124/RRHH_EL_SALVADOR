<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetallesHorasExtras extends Model
{
    protected $table = 'DETALLES_HORASEXTRAS';
    protected $primaryKey = 'ID_DETALLEHORAEXTRA';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'CANTIDADHORAS' => 'decimal:2',
        'MONTOAPAGAR' => 'decimal:2',
    ];

    public function horasExtras(): BelongsTo
    {
        return $this->belongsTo(HorasExtras::class, 'ID_HORASEXTRAS', 'ID_HORASEXTRAS');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'ID_EMPLEADO', 'ID_EMPLEADO');
    }

    public function planilla(): BelongsTo
    {
        return $this->belongsTo(Planilla::class, 'ID_PLANILLA', 'ID_PLANILLA');
    }
}
