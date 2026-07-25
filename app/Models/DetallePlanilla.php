<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetallePlanilla extends Model
{
    protected $table = 'DETALLE_PLANILLA';
    protected $primaryKey = 'ID_DETALLEPLANILLA';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'ES_EVENTUAL' => 'boolean',
        'JUBILADO' => 'boolean',
        'APLICA_ISSS' => 'boolean',
        'APLICA_AFP' => 'boolean',
        'APLICA_RENTA_TABLA' => 'boolean',
        'APLICA_RENTA_FIJA' => 'boolean',
        'PORCENTAJE_RENTA_FIJA' => 'decimal:2',
        'APLICA_INSAFORP' => 'boolean',
        'SALARIO_BASE' => 'decimal:2',
        'DIASLABORADOS' => 'decimal:2',
        'SALARIO_DIAS' => 'decimal:2',
        'HORAEXTRAS' => 'decimal:2',
        'PRODUCTIVIDAD' => 'decimal:2',
        'COMISION' => 'decimal:2',
        'OTROS_INGRESOS' => 'decimal:2',
        'DEVENGADO_GRAVADO' => 'decimal:2',
        'DEVENGADO_EXENTO' => 'decimal:2',
        'TOTAL_DEVENGADO' => 'decimal:2',
        'AFP_EMPLEADO' => 'decimal:2',
        'ISSS_EMPLEADO' => 'decimal:2',
        'RENTA_EMPLEADO' => 'decimal:2',
        'DESCUENTOS_LEY' => 'decimal:2',
        'OTRO_DESCUENTOS' => 'decimal:2',
        'PRESTAMOS' => 'decimal:2',
        'ANTICIPO' => 'decimal:2',
        'TOTAL_DEDUCCIONES' => 'decimal:2',
        'LIQUIDO_A_RECIBIR' => 'decimal:2',
        'AFP_PATRONAL' => 'decimal:2',
        'ISSS_PATRONAL' => 'decimal:2',
        'INSAFORP_PATRONAL' => 'decimal:2',
    ];

    public function planilla(): BelongsTo
    {
        return $this->belongsTo(Planilla::class, 'ID_PLANILLA', 'ID_PLANILLA');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'ID_EMPLEADO', 'ID_EMPLEADO');
    }
}
