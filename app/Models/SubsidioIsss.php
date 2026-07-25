<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubsidioIsss extends Model
{
    protected $table = 'SUBSIDIO_ISSS';
    protected $primaryKey = 'ID_SUBSIDIO';
    public $incrementing = true;
    public $timestamps = false;

    protected $casts = [
        'SALARIO_DIARIO_PROMEDIO' => 'decimal:4',
        'MONTO_SUBSIDIO_CALCULADO_ISSS' => 'decimal:2',
        'MONTO_PAGADO_POR_PATRONO' => 'decimal:2',
        'FECHA_COBRO_ISSS' => 'date',
    ];

    public function incapacidad(): BelongsTo
    {
        return $this->belongsTo(Incapacidad::class, 'ID_INCAPACIDAD', 'ID_INCAPACIDAD');
    }

    public function planilla(): BelongsTo
    {
        return $this->belongsTo(Planilla::class, 'ID_PLANILLA', 'ID_PLANILLA');
    }
}
